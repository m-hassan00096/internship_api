<?php

namespace App\Services\V1\Vendor;

use App\Enums\RegistrationStatus;
use App\Enums\UserActive;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class VendorRegistrationService
{
    /**
     * تسجيل طلب انضمام جديد كـ Vendor وتوثيق الحالة الأولية في السجل الزمني
     *
     * @param  array<string, mixed>  $data
     */
    public function submitRegistration(array $data): VendorRegistration
    {
        return DB::transaction(function () use ($data): VendorRegistration {
            $year = date('Y');
            $nextId = (VendorRegistration::max('id') ?? 0) + 1;
            $regNumber = "REG-{$year}-".str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

            $registration = VendorRegistration::create([
                'registration_number' => $regNumber,
                'vendor_name' => $data['vendorName'] ?? $data['vendor_name'],
                'business_type_id' => $data['businessTypeId'] ?? $data['business_type_id'],
                'contact_name' => $data['contactName'] ?? $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'has_whatsapp' => (bool) ($data['hasWhatsapp'] ?? $data['has_whatsapp'] ?? false),
                'status' => RegistrationStatus::NEW,
            ]);

            $registration->histories()->create([
                'status' => RegistrationStatus::NEW,
                'notes' => null,
                'changed_by' => null,
            ]);

            return $registration;
        });
    }

    /**
     * تعيين كلمة المرور وتفعيل الحساب وتسجيل الدخول المباشر
     */
    public function setPasswordAndActivate(string $email, string $token, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Password::broker()->tokenExists($user, $token)) {
            throw ValidationException::withMessages([
                'token' => [__('vendor/registration.validation.invalid_token')],
            ]);
        }

        return DB::transaction(function () use ($user, $password): User {
            $user->update([
                'password' => $password,
                'user_active' => UserActive::ACTIVE,
            ]);

            Password::broker()->deleteToken($user);

            Auth::guard('web')->login($user);

            setPermissionsTeamId($user->id);

            return $user;
        });
    }
}
