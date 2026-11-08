<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserScope;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;
use App\Models\User;
use App\Models\VendorRegistration;
use App\Services\V1\Vendor\VendorRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private VendorRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VendorRegistrationService::class);
    }

    public function test_user_can_submit_vendor_registration(): void
    {
        $payload = [
            'vendorName' => 'معرض السعادة للأثاث',
            'businessType' => 'شركة أثاث ومفروشات',
            'contactName' => 'أحمد محمود',
            'email' => 'ahmed.vendor@example.com',
            'phone' => '01012345678',
            'hasWhatsapp' => true,
        ];

        $response = $this->postJson(route('vendor.registrations.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('vendor_registrations', [
            'vendor_name' => 'معرض السعادة للأثاث',
            'email' => 'ahmed.vendor@example.com',
            'phone' => '01012345678',
            'status' => RegistrationStatus::NEW->value,
        ]);
    }

    public function test_registration_submission_validates_required_fields_and_uniqueness(): void
    {
        // 1. اختبار الحقول الإجبارية
        $response = $this->postJson(route('vendor.registrations.store'), []);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);

        // 2. اختبار عدم تكرار إيميل مستخدم مسجل مسبقاً
        User::factory()->create(['email' => 'exist@example.com']);
        $responseDuplicate = $this->postJson(route('vendor.registrations.store'), [
            'vendor_name' => 'معرض تجريبي',
            'business_type' => 'تجارة عامة',
            'contact_name' => 'محمد',
            'email' => 'exist@example.com',
            'phone' => '01099998888',
        ]);
        $responseDuplicate->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_registration_approval_creates_user_and_sends_setup_email(): void
    {
        Mail::fake();

        $registration = VendorRegistration::create([
            'registration_number' => 'REG-2026-0001',
            'vendor_name' => 'معرض الخشب النبيل',
            'business_type' => 'صناعة غرف نوم',
            'contact_name' => 'كريم مصطفى',
            'email' => 'karim@example.com',
            'phone' => '01511223344',
            'status' => RegistrationStatus::NEW,
        ]);

        $result = $this->service->approveAndGenerateSetupLink($registration->id);

        $this->assertNotNull($result['setupUrl']);
        $this->assertEquals('karim@example.com', $result['user']->email);

        // تحقق من إنشاء المستخدم الفيندور
        $this->assertDatabaseHas('users', [
            'email' => 'karim@example.com',
            'role' => UserScope::VENDOR->value,
            'is_active' => false,
        ]);

        // تحقق من إرسال الإيميل
        Mail::assertSent(VendorApprovedMail::class, function ($mail) {
            return $mail->hasTo('karim@example.com') && ! empty($mail->setupUrl);
        });

        // تحقق من تحديث حالة الطلب
        $this->assertDatabaseHas('vendor_registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::APPROVED->value,
        ]);
    }

    public function test_registration_rejection_records_reason_and_sends_email(): void
    {
        Mail::fake();

        $registration = VendorRegistration::create([
            'registration_number' => 'REG-2026-0002',
            'vendor_name' => 'معرض الأمل',
            'business_type' => 'تجارة موبيليا',
            'contact_name' => 'حسن إبراهيم',
            'email' => 'hassan@example.com',
            'phone' => '01299887766',
            'status' => RegistrationStatus::NEW,
        ]);

        $reason = 'بيانات السجل التجاري غير مطابقة لاسم المعرض.';
        $this->service->rejectRegistration($registration->id, $reason);

        // تحقق من تحديث حالة الطلب وسبب الرفض
        $this->assertDatabaseHas('vendor_registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::REJECTED->value,
            'rejection_reason' => $reason,
        ]);

        // تحقق من إرسال إيميل الرفض
        Mail::assertSent(VendorRejectedMail::class, function ($mail) use ($reason) {
            return $mail->hasTo('hassan@example.com') && $mail->reason === $reason;
        });
    }

    public function test_vendor_can_setup_password_and_activate_account(): void
    {
        // تجهيز مستخدم فيندور غير مفعل برمز تفعيل
        $vendor = User::factory()->create([
            'email' => 'newvendor@example.com',
            'role' => UserScope::VENDOR,
            'is_active' => false,
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

        // تحقق من تفعيل الحساب وتغيير الباسورد
        $vendor->refresh();
        $this->assertTrue((bool) $vendor->is_active);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $vendor->password));

        // تحقق من تسجيل دخول المستخدم بالـ session
        $this->assertAuthenticatedAs($vendor, 'web');
    }

    public function test_setup_password_fails_with_invalid_token(): void
    {
        $vendor = User::factory()->create([
            'email' => 'invalidtoken@example.com',
            'role' => UserScope::VENDOR,
            'is_active' => false,
        ]);

        $payload = [
            'token' => 'invalid-or-fake-token-12345',
            'email' => 'invalidtoken@example.com',
            'password' => 'NewSecurePassword123!',
            'passwordConfirmation' => 'NewSecurePassword123!',
        ];

        $response = $this->postJson(route('set-password'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);

        $vendor->refresh();
        $this->assertFalse((bool) $vendor->is_active);
    }

    public function test_registration_status_localization(): void
    {
        app()->setLocale('ar');
        $this->assertEquals('جديد', RegistrationStatus::NEW->label());
        $this->assertEquals('قيد المراجعة', RegistrationStatus::UNDER_REVIEW->label());
        $this->assertEquals('معتمد', RegistrationStatus::toString(RegistrationStatus::APPROVED->value));
        $this->assertEquals('غير معروف', RegistrationStatus::toString(999));
        $this->assertEquals('تم استلام طلبكم بنجاح وجاري مراجعته من فريق المنصة.', __('vendor/registration.success_submission'));
        $this->assertEquals('تم إنشاء كلمة المرور وتفعيل حسابك بنجاح، مرحباً بك في لوحة التحكم.', __('vendor/registration.setup_password_success'));

        app()->setLocale('en');
        $this->assertEquals('New', RegistrationStatus::NEW->label());
        $this->assertEquals('Under Review', RegistrationStatus::UNDER_REVIEW->label());
        $this->assertEquals('Approved', RegistrationStatus::toString(RegistrationStatus::APPROVED->value));
        $this->assertEquals('Unknown', RegistrationStatus::toString(999));
        $this->assertEquals('Your application has been received successfully and is under review by our team.', __('vendor/registration.success_submission'));
        $this->assertEquals('Password created and account activated successfully, welcome to your dashboard.', __('vendor/registration.setup_password_success'));
    }
}
