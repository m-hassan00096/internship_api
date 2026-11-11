<?php

namespace App\Services\V1\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Exceptions\InvalidRegistrationActionException;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;
use App\Models\Permission;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class VendorRegistrationService
{
    /**
     * جلب قائمة طلبات التسجيل مع إمكانية الفلترة بالحالة والبحث
     */
    public function getRegistrations(?int $status = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return VendorRegistration::query()
            ->with('businessType')
            ->when($status !== null, function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $escapedSearch = addcslashes($search, '%_\\');
                $like = "%{$escapedSearch}%";

                $query->where(function (Builder $subQuery) use ($like): void {
                    $subQuery->where('registration_number', 'like', $like)
                        ->orWhere('vendor_name', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * جلب تفاصيل طلب التسجيل بالمعرف أو برقم التسجيل مع السجل الزمني
     */
    public function getDetails(int|string $id): VendorRegistration
    {
        return VendorRegistration::query()
            ->with(['businessType', 'user', 'histories.changedBy'])
            ->where(function (Builder $query) use ($id): void {
                if (is_numeric($id)) {
                    $query->where('id', (int) $id);
                } else {
                    $query->where('registration_number', $id);
                }
            })
            ->firstOrFail();
    }

    /**
     * تحديث مرحلة الطلب وتوثيق التغيير في السجل الزمني
     */
    public function changeStatus(
        int|string $id,
        int|RegistrationStatus $status,
        ?string $notes = null,
        ?User $adminUser = null
    ): VendorRegistration {
        return DB::transaction(function () use ($id, $status, $notes, $adminUser): VendorRegistration {
            $registration = $this->findForUpdate($id);

            $this->ensureNotFinalized($registration);

            $resolvedStatus = $status instanceof RegistrationStatus
                ? $status
                : RegistrationStatus::from($status);

            $registration->update([
                'status' => $resolvedStatus,
            ]);

            $registration->histories()->create([
                'status' => $resolvedStatus,
                'notes' => $notes,
                'changed_by' => $adminUser?->id,
            ]);

            return $registration;
        });
    }

    /**
     * اعتماد طلب التسجيل وإنشاء حساب المعرض وتوليد رابط التفعيل وإرسال الإيميل
     *
     * @return array{registration: VendorRegistration, user: User, setupUrl: string}
     */
    public function approveAndGenerateSetupLink(
        int|string $id,
        ?User $adminUser = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($id, $adminUser, $notes): array {
            $registration = $this->findForUpdate($id);

            $this->ensureNotFinalized($registration);

            $existingUser = User::query()
                ->where('email', $registration->email)
                ->orWhere('phone', $registration->phone)
                ->exists();

            if ($existingUser) {
                throw new InvalidRegistrationActionException(__('vendor/registration.errors.user_already_exists'));
            }

            $user = User::create([
                'email' => $registration->email,
                'phone' => $registration->phone,
                'role' => UserScope::VENDOR,
                'manager_id' => null,
                'password' => Hash::make(Str::random(32)),
                'user_active' => UserActive::PENDING_PASSWORD,
            ]);

            $previousTeamId = getPermissionsTeamId();

            try {
                setPermissionsTeamId($user->id);
                $ownerRole = Role::findOrCreate('Owner', 'web');
                $vendorPermissions = Permission::forVendor()->pluck('name');

                if ($vendorPermissions->isNotEmpty()) {
                    $ownerRole->syncPermissions($vendorPermissions);
                }

                $user->syncRoles([$ownerRole]);
            } finally {
                setPermissionsTeamId($previousTeamId);
            }

            $token = Password::broker()->createToken($user);
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $setupUrl = "{$frontendUrl}/vendor/set-password?token={$token}&email=".urlencode($user->email);

            $registration->update([
                'status' => RegistrationStatus::APPROVED,
                'user_id' => $user->id,
            ]);

            $registration->histories()->create([
                'status' => RegistrationStatus::APPROVED,
                'notes' => $notes,
                'changed_by' => $adminUser?->id,
            ]);

            Mail::to($user->email)->send(new VendorApprovedMail($setupUrl, $registration->vendor_name));

            return [
                'registration' => $registration,
                'user' => $user,
                'setupUrl' => $setupUrl,
            ];
        });
    }

    /**
     * رفض طلب التسجيل وتسجيل السبب في السجل الزمني وإرسال إيميل الرفض
     */
    public function rejectRegistration(
        int|string $id,
        string $reason,
        ?User $adminUser = null
    ): VendorRegistration {
        return DB::transaction(function () use ($id, $reason, $adminUser): VendorRegistration {
            $registration = $this->findForUpdate($id);

            $this->ensureNotFinalized($registration);

            $registration->update([
                'status' => RegistrationStatus::REJECTED,
            ]);

            $registration->histories()->create([
                'status' => RegistrationStatus::REJECTED,
                'notes' => $reason,
                'changed_by' => $adminUser?->id,
            ]);

            Mail::to($registration->email)->send(new VendorRejectedMail($registration->vendor_name, $reason));

            return $registration;
        });
    }

    private function findForUpdate(int|string $id): VendorRegistration
    {
        return VendorRegistration::query()
            ->where(function (Builder $query) use ($id): void {
                if (is_numeric($id)) {
                    $query->where('id', (int) $id);
                } else {
                    $query->where('registration_number', $id);
                }
            })
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureNotFinalized(VendorRegistration $registration): void
    {
        if (in_array($registration->status, [RegistrationStatus::APPROVED, RegistrationStatus::REJECTED], true)) {
            throw new InvalidRegistrationActionException(__('vendor/registration.errors.already_finalized'));
        }
    }
}
