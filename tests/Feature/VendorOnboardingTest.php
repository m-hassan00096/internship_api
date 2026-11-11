<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Models\BusinessType;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_active_business_types_localized_by_accept_language_header(): void
    {
        $activeType = BusinessType::factory()->create([
            'name' => [
                'ar' => 'معرض أثاث منزلي',
                'en' => 'Home Furniture Showroom',
            ],
            'is_active' => true,
        ]);

        BusinessType::factory()->inactive()->create([
            'name' => [
                'ar' => 'نشاط معطل',
                'en' => 'Inactive Activity',
            ],
        ]);

        $responseAr = $this->withHeader('Accept-Language', 'ar')
            ->getJson(route('vendor.business-types.index'));

        $responseAr->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeType->id)
            ->assertJsonPath('data.0.name', 'معرض أثاث منزلي');

        $responseEn = $this->withHeader('Accept-Language', 'en')
            ->getJson(route('vendor.business-types.index'));

        $responseEn->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeType->id)
            ->assertJsonPath('data.0.name', 'Home Furniture Showroom');
    }

    public function test_user_can_submit_vendor_registration(): void
    {
        $businessType = BusinessType::factory()->create();

        $payload = [
            'vendorName' => 'معرض السعادة للأثاث',
            'businessTypeId' => $businessType->id,
            'contactName' => 'أحمد محمود',
            'email' => 'ahmed.vendor@example.com',
            'phone' => '01012345678',
            'hasWhatsapp' => true,
        ];

        $response = $this->postJson(route('vendor.registrations.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonMissingPath('data.registrationNumber')
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('vendor_registrations', [
            'vendor_name' => 'معرض السعادة للأثاث',
            'business_type_id' => $businessType->id,
            'email' => 'ahmed.vendor@example.com',
            'phone' => '01012345678',
            'has_whatsapp' => true,
            'status' => RegistrationStatus::NEW->value,
        ]);

        $registration = VendorRegistration::where('email', 'ahmed.vendor@example.com')->firstOrFail();

        $this->assertDatabaseHas('vendor_registration_histories', [
            'vendor_registration_id' => $registration->id,
            'status' => RegistrationStatus::NEW->value,
            'changed_by' => null,
        ]);
    }

    public function test_registration_submission_validates_required_fields_business_type_and_uniqueness(): void
    {
        $activeType = BusinessType::factory()->create();
        $inactiveType = BusinessType::factory()->inactive()->create();

        // 1. اختبار الحقول الإجبارية
        $this->postJson(route('vendor.registrations.store'), [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);

        // 2. اختبار رفض نوع النشاط غير المفعل
        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.registrations.store'), [
                'vendorName' => 'معرض تجريبي',
                'businessTypeId' => $inactiveType->id,
                'contactName' => 'محمد',
                'email' => 'inactive.type@example.com',
                'phone' => '01099998881',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'نوع النشاط المختار غير صالح أو غير مفعل.');

        // 3. اختبار عدم تكرار إيميل مستخدم مسجل مسبقاً
        User::factory()->create(['email' => 'exist@example.com']);

        $this->postJson(route('vendor.registrations.store'), [
            'vendor_name' => 'معرض تجريبي',
            'business_type_id' => $activeType->id,
            'contact_name' => 'محمد',
            'email' => 'exist@example.com',
            'phone' => '01099998888',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_previously_rejected_applicant_can_resubmit_registration(): void
    {
        $businessType = BusinessType::factory()->create();

        VendorRegistration::factory()->rejected()->create([
            'business_type_id' => $businessType->id,
            'email' => 'retry@example.com',
            'phone' => '01055556666',
        ]);

        $response = $this->postJson(route('vendor.registrations.store'), [
            'vendorName' => 'معرض الفرصة الثانية',
            'businessTypeId' => $businessType->id,
            'contactName' => 'طارق علي',
            'email' => 'retry@example.com',
            'phone' => '01055556666',
            'hasWhatsapp' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);

        $this->assertSame(2, VendorRegistration::where('email', 'retry@example.com')->count());
    }

    public function test_vendor_can_setup_password_and_activate_account(): void
    {
        $vendor = User::factory()->pendingPassword()->create([
            'email' => 'newvendor@example.com',
            'role' => UserScope::VENDOR,
        ]);

        setPermissionsTeamId($vendor->id);
        $ownerRole = Role::findOrCreate('Owner', 'web');
        $vendor->syncRoles([$ownerRole]);

        $token = Password::broker()->createToken($vendor);

        $payload = [
            'token' => $token,
            'email' => 'newvendor@example.com',
            'password' => 'NewSecurePassword123!',
            'passwordConfirmation' => 'NewSecurePassword123!',
        ];

        $response = $this->postJson(route('set-password'), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'email',
                        'scope',
                    ],
                    'roles',
                    'permissions',
                ],
            ]);

        $vendor->refresh();
        $this->assertSame(UserActive::ACTIVE, $vendor->user_active);
        $this->assertTrue($vendor->isActive());
        $this->assertTrue(Hash::check('NewSecurePassword123!', $vendor->password));
        $this->assertAuthenticatedAs($vendor, 'web');
    }

    public function test_setup_password_fails_with_invalid_token(): void
    {
        $vendor = User::factory()->pendingPassword()->create([
            'email' => 'invalidtoken@example.com',
            'role' => UserScope::VENDOR,
        ]);

        $payload = [
            'token' => 'invalid-or-fake-token-12345',
            'email' => 'invalidtoken@example.com',
            'password' => 'NewSecurePassword123!',
            'passwordConfirmation' => 'NewSecurePassword123!',
        ];

        $response = $this->postJson(route('set-password'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);

        $vendor->refresh();
        $this->assertSame(UserActive::PENDING_PASSWORD, $vendor->user_active);
    }

    public function test_setup_password_requires_camel_case_password_confirmation(): void
    {
        $vendor = User::factory()->pendingPassword()->create([
            'email' => 'camel@example.com',
            'role' => UserScope::VENDOR,
        ]);

        $token = Password::broker()->createToken($vendor);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('set-password'), [
                'token' => $token,
                'email' => 'camel@example.com',
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'تأكيد كلمة المرور غير متطابق.');

        $this->assertSame(UserActive::PENDING_PASSWORD, $vendor->fresh()->user_active);
    }

    public function test_pending_password_vendor_cannot_login_and_receives_password_setup_message(): void
    {
        User::factory()->pendingPassword()->create([
            'email' => 'pending.vendor@example.com',
            'role' => UserScope::VENDOR,
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/vendor/auth/login', [
                'login' => 'pending.vendor@example.com',
                'password' => 'AnyPassword123!',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'يجب تعيين كلمة المرور الجديدة من رابط التفعيل المرسل إلى بريدك الإلكتروني قبل تسجيل الدخول.');

        $this->withHeader('Accept-Language', 'en')
            ->postJson('/api/v1/vendor/auth/login', [
                'login' => 'pending.vendor@example.com',
                'password' => 'AnyPassword123!',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Please set your new password using the activation link sent to your email before logging in.');
    }

    public function test_inactive_user_cannot_login_with_valid_password_and_receives_inactive_message(): void
    {
        User::factory()->inactive()->create([
            'email' => 'suspended.vendor@example.com',
            'role' => UserScope::VENDOR,
            'password' => 'SecretPass123!',
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/vendor/auth/login', [
                'login' => 'suspended.vendor@example.com',
                'password' => 'WrongPass123!',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/vendor/auth/login', [
                'login' => 'suspended.vendor@example.com',
                'password' => 'SecretPass123!',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'حسابك غير نشط، يرجى التواصل مع الإدارة.');
    }

    public function test_mobile_client_login_respects_user_active_enum_states(): void
    {
        User::factory()->pendingPassword()->create([
            'email' => 'pending.client@example.com',
            'role' => UserScope::CLIENT,
        ]);

        User::factory()->inactive()->create([
            'email' => 'inactive.client@example.com',
            'role' => UserScope::CLIENT,
            'password' => 'SecretPass123!',
        ]);

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/mobile/auth/login', [
                'login' => 'pending.client@example.com',
                'password' => 'AnyPassword123!',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'يجب تعيين كلمة المرور الجديدة من رابط التفعيل المرسل إلى بريدك الإلكتروني قبل تسجيل الدخول.');

        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/v1/mobile/auth/login', [
                'login' => 'inactive.client@example.com',
                'password' => 'SecretPass123!',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'حسابك غير نشط، يرجى التواصل مع الإدارة.');
    }

    public function test_user_active_localization(): void
    {
        app()->setLocale('ar');
        $this->assertSame('غير نشط', UserActive::INACTIVE->label());
        $this->assertSame('نشط', UserActive::ACTIVE->label());
        $this->assertSame('بانتظار تعيين كلمة المرور', UserActive::PENDING_PASSWORD->label());
        $this->assertSame('غير معروف', UserActive::toString(999));
        $this->assertFalse(UserActive::PENDING_PASSWORD->isActive());
        $this->assertTrue(UserActive::ACTIVE->isActive());

        app()->setLocale('en');
        $this->assertSame('Inactive', UserActive::INACTIVE->label());
        $this->assertSame('Active', UserActive::ACTIVE->label());
        $this->assertSame('Pending Password Setup', UserActive::PENDING_PASSWORD->label());
        $this->assertSame('Unknown', UserActive::toString(999));
    }

    public function test_registration_status_localization(): void
    {
        app()->setLocale('ar');
        $this->assertSame('جديد', RegistrationStatus::NEW->label());
        $this->assertSame('قيد المراجعة', RegistrationStatus::UNDER_REVIEW->label());
        $this->assertSame('معتمد', RegistrationStatus::toString(RegistrationStatus::APPROVED->value));
        $this->assertSame('غير معروف', RegistrationStatus::toString(999));
        $this->assertSame('تم استلام طلبكم بنجاح وجاري مراجعته من فريق المنصة.', __('vendor/registration.success_submission'));
        $this->assertSame('تم إنشاء كلمة المرور وتفعيل حسابك بنجاح، مرحباً بك في لوحة التحكم.', __('vendor/registration.setup_password_success'));

        app()->setLocale('en');
        $this->assertSame('New', RegistrationStatus::NEW->label());
        $this->assertSame('Under Review', RegistrationStatus::UNDER_REVIEW->label());
        $this->assertSame('Approved', RegistrationStatus::toString(RegistrationStatus::APPROVED->value));
        $this->assertSame('Unknown', RegistrationStatus::toString(999));
        $this->assertSame('Your application has been received successfully and is under review by our team.', __('vendor/registration.success_submission'));
        $this->assertSame('Password created and account activated successfully, welcome to your dashboard.', __('vendor/registration.setup_password_success'));
    }

    public function test_accept_language_header_changes_response_language(): void
    {
        $businessType = BusinessType::factory()->create();

        $payload = [
            'vendorName' => 'معرض السعادة للأثاث',
            'businessTypeId' => $businessType->id,
            'contactName' => 'أحمد محمود',
            'email' => 'ahmed.lang.test@example.com',
            'phone' => '01019999888',
            'hasWhatsapp' => true,
        ];

        $responseAr = $this->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.registrations.store'), $payload);

        $responseAr->assertCreated()
            ->assertJsonPath('message', 'تم استلام طلبكم بنجاح وجاري مراجعته من فريق المنصة.');

        $payloadEn = array_merge($payload, [
            'email' => 'ahmed.lang.en@example.com',
            'phone' => '01019999777',
        ]);

        $responseEn = $this->withHeader('Accept-Language', 'en')
            ->postJson(route('vendor.registrations.store'), $payloadEn);

        $responseEn->assertCreated()
            ->assertJsonPath('message', 'Your application has been received successfully and is under review by our team.');
    }
}
