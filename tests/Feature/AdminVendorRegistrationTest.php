<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;
use App\Models\BusinessType;
use App\Models\User;
use App\Models\VendorRegistration;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminVendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public static function nonAdminScopes(): array
    {
        return [
            'client user' => [UserScope::CLIENT],
            'vendor user' => [UserScope::VENDOR],
        ];
    }

    public function test_unauthenticated_user_cannot_access_admin_registration_endpoints(): void
    {
        $registration = VendorRegistration::factory()->create();

        $this->getJson(route('admin.vendor-registrations.index'))->assertUnauthorized();
        $this->getJson(route('admin.vendor-registrations.show', $registration->id))->assertUnauthorized();
        $this->patchJson(route('admin.vendor-registrations.change-status', $registration->id), [
            'status' => RegistrationStatus::UNDER_REVIEW->value,
        ])->assertUnauthorized();
        $this->postJson(route('admin.vendor-registrations.approve', $registration->id))->assertUnauthorized();
        $this->postJson(route('admin.vendor-registrations.reject', $registration->id), [
            'reason' => 'سبب مرفوض هنا',
        ])->assertUnauthorized();
    }

    #[DataProvider('nonAdminScopes')]
    public function test_non_admin_user_cannot_access_admin_registration_endpoints(UserScope $scope): void
    {
        $user = User::factory()->create(['role' => $scope]);
        $registration = VendorRegistration::factory()->create();

        $this->actingAs($user, 'web')
            ->getJson(route('admin.vendor-registrations.index'))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($user, 'web')
            ->postJson(route('admin.vendor-registrations.approve', $registration->id))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertSame(RegistrationStatus::NEW, $registration->fresh()->status);
    }

    public function test_admin_can_list_filter_and_search_vendor_registrations(): void
    {
        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $type = BusinessType::factory()->create([
            'name' => [
                'ar' => 'مطابخ مدمجة',
                'en' => 'Built-in Kitchens',
            ],
        ]);

        $newReg = VendorRegistration::factory()->create([
            'registration_number' => 'REG-2026-0010',
            'vendor_name' => 'معرض النيل الحديث',
            'business_type_id' => $type->id,
            'status' => RegistrationStatus::NEW,
        ]);

        VendorRegistration::factory()->underReview()->create([
            'registration_number' => 'REG-2026-0011',
            'vendor_name' => 'معرض القاهرة للديكور',
            'business_type_id' => $type->id,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'en')
            ->getJson(route('admin.vendor-registrations.index', [
                'status' => RegistrationStatus::NEW->value,
                'search' => '0010',
                'perPage' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $newReg->id)
            ->assertJsonPath('data.items.0.registrationNumber', 'REG-2026-0010')
            ->assertJsonPath('data.items.0.businessTypeName', 'Built-in Kitchens')
            ->assertJsonMissingPath('data.items.0.hasWhatsapp')
            ->assertJsonPath('data.items.0.status.value', RegistrationStatus::NEW->value)
            ->assertJsonPath('data.items.0.status.name', 'NEW')
            ->assertJsonPath('data.items.0.status.label', 'New')
            ->assertJsonPath('data.pagination.currentPage', 1)
            ->assertJsonPath('data.pagination.perPage', 10)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_admin_list_validates_status_filter_parameter(): void
    {
        $admin = User::factory()->create(['role' => UserScope::ADMIN]);

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.vendor-registrations.index', ['status' => 999]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_view_registration_details_with_timeline_by_id_or_number(): void
    {
        $admin = User::factory()->create([
            'email' => 'reviewer.admin@example.com',
            'role' => UserScope::ADMIN,
        ]);
        $type = BusinessType::factory()->create([
            'name' => [
                'ar' => 'غرف نوم',
                'en' => 'Bedrooms',
            ],
        ]);
        $registration = VendorRegistration::factory()->underReview()->create([
            'registration_number' => 'REG-2026-0055',
            'business_type_id' => $type->id,
        ]);

        $registration->histories()->create([
            'status' => RegistrationStatus::NEW,
            'notes' => null,
            'changed_by' => null,
        ]);
        $registration->histories()->create([
            'status' => RegistrationStatus::UNDER_REVIEW,
            'notes' => 'تم التواصل هاتفياً وطلب صور الصالة',
            'changed_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->getJson(route('admin.vendor-registrations.show', 'REG-2026-0055'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $registration->id)
            ->assertJsonPath('data.registrationNumber', 'REG-2026-0055')
            ->assertJsonPath('data.businessType.id', $type->id)
            ->assertJsonPath('data.businessType.name', 'غرف نوم')
            ->assertJsonPath('data.status.value', RegistrationStatus::UNDER_REVIEW->value)
            ->assertJsonPath('data.status.label', 'قيد المراجعة')
            ->assertJsonPath('data.latestNotes', 'تم التواصل هاتفياً وطلب صور الصالة')
            ->assertJsonPath('data.rejectionReason', null)
            ->assertJsonPath('data.reviewer.id', $admin->id)
            ->assertJsonPath('data.reviewer.email', 'reviewer.admin@example.com')
            ->assertJsonCount(2, 'data.timeline');

        $this->actingAs($admin, 'web')
            ->getJson(route('admin.vendor-registrations.show', 'REG-9999-9999'))
            ->assertNotFound();
    }

    public function test_admin_can_update_registration_status_and_record_history(): void
    {
        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->create([
            'status' => RegistrationStatus::NEW,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->patchJson(route('admin.vendor-registrations.change-status', $registration->id), [
                'status' => RegistrationStatus::UNDER_REVIEW->value,
                'notes' => 'جاري مراجعة السجل التجاري والبطاقة الضريبية',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تحديث حالة طلب التسجيل بنجاح.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('vendor_registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::UNDER_REVIEW->value,
        ]);

        $this->assertDatabaseHas('vendor_registration_histories', [
            'vendor_registration_id' => $registration->id,
            'status' => RegistrationStatus::UNDER_REVIEW->value,
            'notes' => 'جاري مراجعة السجل التجاري والبطاقة الضريبية',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_admin_status_endpoint_rejects_terminal_statuses(): void
    {
        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->create();

        $this->actingAs($admin, 'web')
            ->patchJson(route('admin.vendor-registrations.change-status', $registration->id), [
                'status' => RegistrationStatus::APPROVED->value,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_approve_registration_creating_vendor_owner_and_sending_email(): void
    {
        Mail::fake();
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::where('email', 'admin1@example.com')->firstOrFail();
        $type = BusinessType::factory()->create();

        $registration = VendorRegistration::factory()->underReview()->create([
            'vendor_name' => 'معرض الخشب النبيل',
            'business_type_id' => $type->id,
            'email' => 'karim@example.com',
            'phone' => '01511223344',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.vendor-registrations.approve', $registration->id), [
                'notes' => 'تم التحقق من كامل المستندات والاعتماد',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم اعتماد طلب التسجيل وإنشاء حساب المعرض وإرسال رابط التفعيل.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('users', [
            'email' => 'karim@example.com',
            'phone' => '01511223344',
            'role' => UserScope::VENDOR->value,
            'user_active' => UserActive::PENDING_PASSWORD->value,
        ]);

        $vendorUser = User::where('email', 'karim@example.com')->firstOrFail();

        $this->assertSame(1, getPermissionsTeamId());

        $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->getJson(route('admin.vendor-registrations.show', $registration->id))
            ->assertOk()
            ->assertJsonPath('data.vendorAccount.id', $vendorUser->id)
            ->assertJsonPath('data.vendorAccount.userActive.value', UserActive::PENDING_PASSWORD->value)
            ->assertJsonPath('data.vendorAccount.userActive.name', 'PENDING_PASSWORD')
            ->assertJsonPath('data.vendorAccount.userActive.label', 'بانتظار تعيين كلمة المرور');

        setPermissionsTeamId($vendorUser->id);
        $this->assertTrue($vendorUser->hasRole('Owner'));
        $this->assertTrue($vendorUser->hasPermissionTo('manage products'));

        $this->assertDatabaseHas('vendor_registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::APPROVED->value,
            'user_id' => $vendorUser->id,
        ]);

        $this->assertDatabaseHas('vendor_registration_histories', [
            'vendor_registration_id' => $registration->id,
            'status' => RegistrationStatus::APPROVED->value,
            'notes' => 'تم التحقق من كامل المستندات والاعتماد',
            'changed_by' => $admin->id,
        ]);

        $capturedSetupUrl = '';
        Mail::assertSent(VendorApprovedMail::class, function (VendorApprovedMail $mail) use (&$capturedSetupUrl): bool {
            $capturedSetupUrl = $mail->setupUrl;

            return $mail->hasTo('karim@example.com')
                && str_contains($mail->setupUrl, '/vendor/set-password?token=');
        });

        parse_str((string) parse_url($capturedSetupUrl, PHP_URL_QUERY), $queryParams);

        $this->postJson(route('set-password'), [
            'email' => $queryParams['email'] ?? '',
            'token' => $queryParams['token'] ?? '',
            'password' => 'StrongVendorPass123!',
            'passwordConfirmation' => 'StrongVendorPass123!',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'karim@example.com')
            ->assertJsonPath('data.roles.0', 'Owner');

        $this->assertSame(UserActive::ACTIVE, $vendorUser->fresh()->user_active);
    }

    public function test_admin_cannot_approve_already_finalized_registration(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->approved()->create();

        $this->actingAs($admin, 'web')
            ->postJson(route('admin.vendor-registrations.approve', $registration->id))
            ->assertBadRequest()
            ->assertJsonPath('success', false);

        Mail::assertNothingSent();
    }

    public function test_admin_can_reject_registration_recording_reason_in_history_and_sending_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->create([
            'vendor_name' => 'معرض الأمل',
            'email' => 'hassan@example.com',
            'phone' => '01299887766',
        ]);

        $reason = 'بيانات السجل التجاري غير مطابقة لاسم المعرض.';

        $response = $this->actingAs($admin, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('admin.vendor-registrations.reject', $registration->id), [
                'reason' => $reason,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم رفض طلب التسجيل وإخطار مقدم الطلب بالسبب.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('vendor_registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::REJECTED->value,
        ]);

        $this->assertDatabaseHas('vendor_registration_histories', [
            'vendor_registration_id' => $registration->id,
            'status' => RegistrationStatus::REJECTED->value,
            'notes' => $reason,
            'changed_by' => $admin->id,
        ]);

        Mail::assertSent(VendorRejectedMail::class, function (VendorRejectedMail $mail) use ($reason): bool {
            return $mail->hasTo('hassan@example.com') && $mail->reason === $reason;
        });
    }

    public function test_admin_reject_requires_valid_reason(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->create();

        $this->actingAs($admin, 'web')
            ->postJson(route('admin.vendor-registrations.reject', $registration->id), [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(RegistrationStatus::NEW, $registration->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_admin_cannot_reject_or_update_status_of_finalized_registration(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserScope::ADMIN]);
        $registration = VendorRegistration::factory()->approved()->create();

        $this->actingAs($admin, 'web')
            ->patchJson(route('admin.vendor-registrations.change-status', $registration->id), [
                'status' => RegistrationStatus::UNDER_REVIEW->value,
            ])
            ->assertBadRequest()
            ->assertJsonPath('success', false);

        $this->actingAs($admin, 'web')
            ->postJson(route('admin.vendor-registrations.reject', $registration->id), [
                'reason' => 'سبب متأخر بعد الاعتماد',
            ])
            ->assertBadRequest()
            ->assertJsonPath('success', false);

        Mail::assertNothingSent();
    }
}
