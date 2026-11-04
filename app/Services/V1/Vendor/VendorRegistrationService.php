<?php

namespace App\Services\V1\Vendor;

use App\Enums\RegistrationStatus;
use App\Enums\UserScope;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class VendorRegistrationService
{
    /**
     * تسجيل طلب انضمام جديد كـ Vendor
     */
    public function submitRegistration(array $data): VendorRegistration
    {
        $year = date('Y');
        $nextId = (VendorRegistration::max('id') ?? 0) + 1;
        $regNumber = "REG-{$year}-".str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

        return VendorRegistration::create([
            'registration_number' => $regNumber,
            'vendor_name' => $data['vendor_name'],
            'business_type' => $data['business_type'],
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'has_whatsapp' => $data['has_whatsapp'] ?? false,
            'status' => RegistrationStatus::NEW,
        ]);
    }

    /**
     * جلب قائمة طلبات التسجيل مع إمكانية الفلترة بالحالة
     */
    public function getRegistrations(int|string|null $status = null): LengthAwarePaginator
    {
        return VendorRegistration::with('reviewer')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15);
    }

    /**
     * جلب تفاصيل طلب التسجيل بالمعرف أو برقم التسجيل
     */
    public function getDetails(int|string $id): VendorRegistration
    {
        return VendorRegistration::with('reviewer')
            ->where(function ($query) use ($id) {
                if (is_numeric($id)) {
                    $query->where('id', $id);
                } else {
                    $query->where('registration_number', $id);
                }
            })
            ->firstOrFail();
    }

    /**
     * تحديث مرحلة الطلب والملاحظات
     */
    public function updateStatus(int|string $id, int|string|RegistrationStatus $status, ?string $notes = null, ?User $adminUser = null): VendorRegistration
    {
        $registration = $this->getDetails($id);
        $statusValue = $status instanceof RegistrationStatus ? $status->value : (int) $status;

        $registration->update([
            'status' => $statusValue,
            'notes' => $notes,
            'reviewed_by' => $adminUser?->id,
        ]);

        return $registration->fresh('reviewer');
    }

    /**
     * اعتماد طلب التسجيل وإنشاء حساب المعرض وتوليد رابط التفعيل وإرسال الإيميل
     */
    public function approveAndGenerateSetupLink(int|string $id, ?User $adminUser = null): array
    {
        $registration = $this->getDetails($id);

        return DB::transaction(function () use ($registration, $adminUser) {
            $user = User::firstOrCreate(
                ['email' => $registration->email],
                [
                    'phone' => $registration->phone,
                    'role' => UserScope::VENDOR,
                    'manager_id' => null,
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => false,
                ]
            );

            // ضبط سياق الـ Team للفيندور وتعيين دور Owner
            setPermissionsTeamId($user->id);
            $ownerRole = Role::findOrCreate('Owner', 'web');
            $user->syncRoles([$ownerRole]);

            // توليد Token التفعيل
            $token = Password::broker()->createToken($user);
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $setupUrl = "{$frontendUrl}/vendor/setup-password?token={$token}&email=".urlencode($user->email);

            // تحديث حالة الطلب
            $registration->update([
                'status' => RegistrationStatus::APPROVED,
                'user_id' => $user->id,
                'reviewed_by' => $adminUser?->id,
            ]);

            // إرسال إيميل التهنئة
            Mail::to($user->email)->send(new VendorApprovedMail($setupUrl, $registration->vendor_name));

            return [
                'registration' => $registration,
                'user' => $user,
                'setupUrl' => $setupUrl,
            ];
        });
    }

    /**
     * رفض طلب التسجيل وتسجيل السبب وإرسال إيميل الرفض للتاجر
     */
    public function rejectRegistration(int|string $id, string $reason, ?User $adminUser = null): VendorRegistration
    {
        $registration = $this->getDetails($id);

        return DB::transaction(function () use ($registration, $reason, $adminUser) {
            $registration->update([
                'status' => RegistrationStatus::REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $adminUser?->id,
            ]);

            // إرسال إيميل الاعتذار مع سبب الرفض
            Mail::to($registration->email)->send(new VendorRejectedMail($registration->vendor_name, $reason));

            return $registration;
        });
    }

    /**
     * تعيين كلمة المرور وتفعيل الحساب وتسجيل الدخول المباشر
     */
    public function setupPasswordAndActivate(string $email, string $token, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Password::broker()->tokenExists($user, $token)) {
            throw ValidationException::withMessages([
                'token' => ['رابط التفعيل غير صالح أو انتهت صلاحيته.'],
            ]);
        }

        return DB::transaction(function () use ($user, $password) {
            $user->update([
                'password' => $password,
                'is_active' => true,
            ]);

            Password::broker()->deleteToken($user);

            Auth::guard('web')->login($user);

            setPermissionsTeamId($user->id);

            return $user;
        });
    }
}
