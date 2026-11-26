<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\PackageStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RoomStatus;
use App\Enums\ShowroomStatus;
use App\Enums\UserScope;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Package;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\ShowroomBranch;
use App\Models\ShowroomContract;
use App\Models\ShowroomVerification;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminShowroomTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_admin_showrooms(): void
    {
        $this->getJson(route('admin.showrooms.index'))->assertUnauthorized();
        $this->postJson(route('admin.showrooms.store'))->assertUnauthorized();
        $this->getJson(route('admin.showrooms.show', 1))->assertUnauthorized();
        $this->patchJson(route('admin.showrooms.update-status', 1))->assertUnauthorized();
        $this->postJson(route('admin.showrooms.contracts.renew', 1))->assertUnauthorized();
        $this->postJson(route('admin.showrooms.verification', 1))->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_access_admin_showrooms(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.showrooms.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_showrooms_with_kpis(): void
    {
        $businessType = BusinessType::factory()->create();

        $showroom1 = Showroom::factory()->create([
            'name' => 'معرض رويال للأثاث',
            'business_type_id' => $businessType->id,
            'status' => ShowroomStatus::ACTIVE,
        ]);
        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom1->id,
            'started_at' => now()->subMonth()->toDateString(),
            'end_at' => now()->addMonths(11)->toDateString(),
            'status' => ContractStatus::ACTIVE,
            'max_rooms' => 50,
            'max_packages' => 10,
        ]);

        $showroom2 = Showroom::factory()->create([
            'name' => 'معرض مودرن هوم',
            'business_type_id' => $businessType->id,
            'status' => ShowroomStatus::SUSPENDED,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.index'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'logo',
                            'isVerified',
                            'businessType',
                            'contactPerson',
                            'status' => ['value', 'label'],
                            'contract',
                            'roomsQuota' => ['used', 'max'],
                            'packagesQuota' => ['used', 'max'],
                        ],
                    ],
                    'kpis' => [
                        'totalShowrooms',
                        'activeContracts',
                        'needingRenewal',
                        'suspendedOrInactive',
                    ],
                    'pagination',
                ],
            ])
            ->assertJsonPath('data.kpis.totalShowrooms', 2)
            ->assertJsonPath('data.kpis.suspendedOrInactive', 1)
            ->assertJsonPath('data.items.1.contract.isExpiringSoon', false);
    }

    public function test_admin_can_filter_and_search_showrooms(): void
    {
        $businessType = BusinessType::factory()->create();

        Showroom::factory()->create([
            'name' => 'معرض رويال للأثاث',
            'business_type_id' => $businessType->id,
            'status' => ShowroomStatus::ACTIVE,
        ]);

        Showroom::factory()->create([
            'name' => 'معرض كايرو كلاسيك',
            'business_type_id' => $businessType->id,
            'status' => ShowroomStatus::SUSPENDED,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.index', [
                'search' => 'رويال',
                'status' => ShowroomStatus::ACTIVE->value,
            ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'معرض رويال للأثاث');
    }

    public function test_admin_can_view_showroom_details(): void
    {
        $showroom = Showroom::factory()->create();
        ShowroomBranch::factory()->create([
            'showrooms_id' => $showroom->id,
            'is_main' => true,
        ]);
        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => ContractStatus::ACTIVE,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.show', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $showroom->id)
            ->assertJsonPath('data.name', $showroom->name)
            ->assertJsonPath('data.activeVerification.status.value', VerificationStatus::UNVERIFIED->value)
            ->assertJsonPath('data.activeVerification.status.name', 'UNVERIFIED')
            ->assertJsonMissingPath('data.isVerified')
            ->assertJsonMissingPath('data.activeVerification.isActive');

        $this->assertNotNull($response->json('data.activeContract'));
        $this->assertNotNull($response->json('data.content.rooms'));
    }

    public function test_vendor_registration_id_is_required_to_create_showroom(): void
    {
        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.showrooms.store'), [
                'contract' => [
                    'startedAt' => now()->toDateString(),
                    'endAt' => now()->addYear()->toDateString(),
                    'amount' => 24000.0,
                    'maxRooms' => 100,
                    'maxPackages' => 10,
                ],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vendorRegistrationId']);
    }

    public function test_admin_can_update_showroom_status(): void
    {
        $showroom = Showroom::factory()->create(['status' => ShowroomStatus::ACTIVE]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.showrooms.update-status', $showroom->id), [
                'status' => ShowroomStatus::SUSPENDED->value,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(ShowroomStatus::SUSPENDED, $showroom->fresh()->status);
    }

    public function test_admin_can_renew_showroom_contract(): void
    {
        Storage::fake('public');

        $showroom = Showroom::factory()->create();

        $payload = [
            'contractNumber' => 'CNT-2027-RENEW-01',
            'startedAt' => now()->addYear()->toDateString(),
            'endAt' => now()->addYears(2)->toDateString(),
            'amount' => 30000.0,
            'maxRooms' => 150,
            'maxPackages' => 20,
            'contractFile' => UploadedFile::fake()->create('contract_renewal.pdf', 150, 'application/pdf'),
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.showrooms.contracts.renew', $showroom->id), $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.contractNumber', 'CNT-2027-RENEW-01')
            ->assertJsonPath('data.maxRooms', 150);

        $this->assertDatabaseHas('showrooms_contract', [
            'showrooms_id' => $showroom->id,
            'contract_number' => 'CNT-2027-RENEW-01',
            'amount' => 30000.0,
        ]);
    }

    public function test_admin_can_manage_and_approve_verification(): void
    {
        $showroom = Showroom::factory()->create();

        $payload = [
            'month' => 6,
            'monthlyPrice' => 500.0,
            'totalAmount' => 3000.0,
            'status' => VerificationStatus::APPROVED->value,
            'startAt' => now()->toDateString(),
            'endAt' => now()->addMonths(6)->toDateString(),
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.showrooms.verification', $showroom->id), $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.month', 6)
            ->assertJsonPath('data.totalAmount', 3000)
            ->assertJsonPath('data.status.value', VerificationStatus::APPROVED->value);

        $this->assertDatabaseHas('showrooms_verification', [
            'showrooms_id' => $showroom->id,
            'month' => 6,
            'total_amount' => 3000.0,
            'status' => VerificationStatus::APPROVED->value,
            'review_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_showroom_from_approved_vendor_registration(): void
    {
        Storage::fake('public');

        $vendorUser = User::factory()->create(['role' => UserScope::VENDOR]);
        $businessType = BusinessType::factory()->create();

        $registration = VendorRegistration::factory()->create([
            'user_id' => $vendorUser->id,
            'business_type_id' => $businessType->id,
            'vendor_name' => 'مصنع الأهرام للأثاث والمطابخ',
            'phone' => '+201099887766',
            'status' => RegistrationStatus::APPROVED,
        ]);

        $payload = [
            'vendorRegistrationId' => $registration->id,
            'branch' => [
                'name' => 'الفرع الرئيسي - العاشر من رمضان',
                'city' => 'الشرقية',
                'address' => 'المنطقة الصناعية الثالثة',
                'phone' => '+201099887766',
            ],
            'contract' => [
                'startedAt' => now()->toDateString(),
                'endAt' => now()->addYear()->toDateString(),
                'amount' => 36000.0,
                'maxRooms' => 120,
                'maxPackages' => 20,
            ],
        ];

        $response = $this
            ->actingAs($this->admin, 'web')
            ->postJson(route('admin.showrooms.store'), $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'مصنع الأهرام للأثاث والمطابخ');

        $this->assertNotNull($response->json('data.id'));
        $this->assertNotNull($response->json('data.contractNumber'));

        $this->assertDatabaseHas('showrooms', [
            'user_id' => $vendorUser->id,
            'business_type_id' => $businessType->id,
            'name' => 'مصنع الأهرام للأثاث والمطابخ',
        ]);
    }

    public function test_admin_can_view_showroom_contracts_tab(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'شركة تراك للأثاث']);
        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'amount' => 30000.0,
            'max_rooms' => 150,
            'max_packages' => 20,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->startOfMonth(),
            'end_at' => now()->addYear(),
        ]);

        ShowroomVerification::factory()->create([
            'showrooms_id' => $showroom->id,
            'month' => 6,
            'monthly_price' => 500.0,
            'total_amount' => 3000.0,
            'status' => VerificationStatus::APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonths(6),
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.contracts', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $showroom->id)
            ->assertJsonPath('data.name', 'شركة تراك للأثاث')
            ->assertJsonPath('data.financialSummary.baseContractAmount', 30000)
            ->assertJsonPath('data.financialSummary.vatPercentage', 15)
            ->assertJsonPath('data.financialSummary.vatAmount', 4500)
            ->assertJsonPath('data.financialSummary.verificationFee', 3000)
            ->assertJsonPath('data.financialSummary.totalAmount', 37500)
            ->assertJsonPath('data.verification.status.value', VerificationStatus::APPROVED->value)
            ->assertJsonPath('data.verification.month', 6)
            ->assertJsonPath('data.contractingParty.companyName', 'شركة تراك للأثاث')
            ->assertJsonMissingPath('data.contracts');
    }

    public function test_admin_can_view_showroom_verification_history(): void
    {
        $showroom = Showroom::factory()->create();

        ShowroomVerification::factory()->create([
            'showrooms_id' => $showroom->id,
            'month' => 6,
            'monthly_price' => 500.0,
            'total_amount' => 3000.0,
            'status' => VerificationStatus::APPROVED,
            'review_by' => $this->admin->id,
        ]);

        ShowroomVerification::factory()->create([
            'showrooms_id' => $showroom->id,
            'month' => 12,
            'monthly_price' => 400.0,
            'total_amount' => 4800.0,
            'status' => VerificationStatus::REJECTED,
            'reject_reason' => 'بيانات غير مطابقة',
            'review_by' => $this->admin->id,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.verifications', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.month', 12)
            ->assertJsonPath('data.0.status.value', VerificationStatus::REJECTED->value)
            ->assertJsonPath('data.0.rejectReason', 'بيانات غير مطابقة')
            ->assertJsonPath('data.0.reviewer.name', $this->admin->name)
            ->assertJsonPath('data.1.month', 6)
            ->assertJsonPath('data.1.status.value', VerificationStatus::APPROVED->value);
    }

    public function test_admin_can_view_showroom_contracts_history(): void
    {
        $showroom = Showroom::factory()->create();

        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'contract_number' => 'CNT-2026-0001',
            'amount' => 30000.0,
            'max_rooms' => 150,
            'max_packages' => 20,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->subMonths(6),
            'end_at' => now()->addMonths(6),
        ]);

        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'contract_number' => 'CNT-2027-0002',
            'amount' => 35000.0,
            'max_rooms' => 180,
            'max_packages' => 25,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->addMonths(7),
            'end_at' => now()->addMonths(19),
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.contracts.history', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.contractNumber', 'CNT-2027-0002')
            ->assertJsonPath('data.0.amount', 35000)
            ->assertJsonPath('data.0.maxRooms', 180)
            ->assertJsonPath('data.1.contractNumber', 'CNT-2026-0001')
            ->assertJsonPath('data.1.amount', 30000);
    }

    public function test_admin_can_view_showroom_rooms_with_kpis_and_filters(): void
    {
        $showroom = Showroom::factory()->create();
        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'max_rooms' => 100,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->startOfMonth(),
            'end_at' => now()->addYear(),
        ]);

        $catLiving = Category::factory()->create(['name' => ['ar' => 'أثاث المعيشة', 'en' => 'Living Room']]);
        $catBed = Category::factory()->create(['name' => ['ar' => 'أثاث النوم', 'en' => 'Bedroom']]);

        Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $catLiving->id,
            'name' => 'غرفة معيشة مودرن',
            'sku' => 'RM-LV-001',
            'price' => 18500.0,
            'status' => RoomStatus::PUBLISHED,
        ]);

        Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $catLiving->id,
            'name' => 'صالون كلاسيكي فاخر',
            'sku' => 'RM-LV-002',
            'price' => 25000.0,
            'status' => RoomStatus::PUBLISHED,
        ]);

        Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $catBed->id,
            'name' => 'غرفة نوم ماستر كينج',
            'sku' => 'RM-BD-003',
            'price' => 32000.0,
            'status' => RoomStatus::DRAFT,
            'current_step' => 2,
        ]);

        Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $catBed->id,
            'name' => 'ركن قهوة مسودة',
            'sku' => 'RM-CF-004',
            'price' => 7000.0,
            'status' => RoomStatus::DRAFT,
            'current_step' => 1,
        ]);

        // 1. All rooms
        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.rooms', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.total', 4)
            ->assertJsonPath('data.kpis.published', 2)
            ->assertJsonPath('data.kpis.draft', 2)
            ->assertJsonPath('data.kpis.archived', 0)
            ->assertJsonPath('data.kpis.draftOrArchived', 2)
            ->assertJsonPath('data.quota.used', 4)
            ->assertJsonPath('data.quota.max', 100)
            ->assertJsonPath('data.quota.remaining', 96)
            ->assertJsonCount(4, 'data.items')
            ->assertJsonPath('data.pagination.total', 4);

        // 2. Filter by status: published
        $responseStatus = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.rooms', [$showroom->id, 'status' => 'published']));

        $responseStatus
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        // 3. Filter by category
        $responseCategory = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.rooms', [$showroom->id, 'categoryId' => $catLiving->id]));

        $responseCategory
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        // 4. Search by SKU
        $responseSearch = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.rooms', [$showroom->id, 'search' => 'RM-BD-003']));

        $responseSearch
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.sku', 'RM-BD-003');
    }

    public function test_admin_can_view_showroom_packages_with_kpis_and_filters(): void
    {
        $showroom = Showroom::factory()->create();
        ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'max_packages' => 20,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->startOfMonth(),
            'end_at' => now()->addYear(),
        ]);

        $cat = Category::factory()->create(['name' => ['ar' => 'صالون', 'en' => 'Salon']]);
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $cat->id,
        ]);

        $package1 = Package::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'باقة تأثيث شقة 3 غرف عصرية',
            'sku' => 'PKG-2026-001',
            'price' => 125000.0,
            'status' => PackageStatus::PUBLISHED,
        ]);
        $package1->rooms()->attach($room->id);

        Package::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'باقة فيلا كلاسيك VIP',
            'sku' => 'PKG-2026-002',
            'price' => 320000.0,
            'status' => PackageStatus::PUBLISHED,
        ]);

        Package::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'باقة مسودة ستوديو',
            'sku' => 'PKG-2026-003',
            'price' => 70000.0,
            'status' => PackageStatus::DRAFT,
            'current_step' => 1,
        ]);

        // 1. All packages
        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.packages', $showroom->id));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.total', 3)
            ->assertJsonPath('data.kpis.published', 2)
            ->assertJsonPath('data.kpis.draft', 1)
            ->assertJsonPath('data.kpis.archived', 0)
            ->assertJsonPath('data.kpis.draftOrArchived', 1)
            ->assertJsonPath('data.quota.used', 3)
            ->assertJsonPath('data.quota.max', 20)
            ->assertJsonPath('data.quota.remaining', 17)
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.pagination.total', 3);

        // 2. Search package
        $responseSearch = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.packages', [$showroom->id, 'search' => 'PKG-2026-001']));

        $responseSearch
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.sku', 'PKG-2026-001')
            ->assertJsonPath('data.items.0.roomsCount', 1);
    }

    public function test_showroom_content_endpoints_return_404_when_showroom_not_found(): void
    {
        $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.rooms', 99999))
            ->assertNotFound();

        $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.showrooms.packages', 99999))
            ->assertNotFound();
    }
}
