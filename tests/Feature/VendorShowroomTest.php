<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Enums\UserScope;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\Showroom;
use App\Models\ShowroomBranch;
use App\Models\ShowroomContract;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorShowroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_showroom_endpoints(): void
    {
        $this->getJson(route('vendor.showroom.show'))->assertUnauthorized();
        $this->getJson(route('vendor.showroom.contract'))->assertUnauthorized();
        $this->getJson(route('vendor.showroom.verification'))->assertUnauthorized();
        $this->postJson(route('vendor.showroom.verification.apply'), ['month' => 6])->assertUnauthorized();
        $this->getJson(route('vendor.showroom.branches'))->assertUnauthorized();
    }

    public function test_non_vendor_user_cannot_access_showroom_endpoints(): void
    {
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this->actingAs($client, 'web')
            ->getJson(route('vendor.showroom.show'))
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_owner_can_view_their_showroom_profile(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $type = BusinessType::factory()->create([
            'name' => ['ar' => 'أثاث مودرن', 'en' => 'Modern Furniture'],
        ]);

        $showroom = Showroom::factory()->create([
            'user_id' => $owner->id,
            'business_type_id' => $type->id,
            'name' => 'معرض تراك للأثاث',
            'slug' => 'track-furniture',
            'bio' => 'معرض ومصنع متخصص في الأثاث المنزلي',
            'phone' => '01012345678',
            'status' => ShowroomStatus::ACTIVE,
        ]);

        $contract = ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'contract_number' => 'CNT-2026-0001',
            'max_rooms' => 20,
            'max_packages' => 5,
            'status' => ContractStatus::ACTIVE,
        ]);

        $response = $this->actingAs($owner, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->getJson(route('vendor.showroom.show'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $showroom->id)
            ->assertJsonPath('data.name', 'معرض تراك للأثاث')
            ->assertJsonPath('data.slug', 'track-furniture')
            ->assertJsonPath('data.bio', 'معرض ومصنع متخصص في الأثاث المنزلي')
            ->assertJsonPath('data.phone', '01012345678')
            ->assertJsonPath('data.businessType.name', 'أثاث مودرن')
            ->assertJsonPath('data.status.value', ShowroomStatus::ACTIVE->value)
            ->assertJsonPath('data.status.label', 'نشط')
            ->assertJsonPath('data.isVerified', false)
            ->assertJsonPath('data.activeContract.contractNumber', 'CNT-2026-0001')
            ->assertJsonPath('data.activeContract.maxRooms', 20)
            ->assertJsonPath('data.activeContract.maxPackages', 5);
    }

    public function test_vendor_employee_views_their_owner_showroom_profile(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $employee = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => $owner->id]);

        $showroom = Showroom::factory()->create([
            'user_id' => $owner->id,
            'name' => 'معرض القمة',
            'slug' => 'qimma-showroom',
        ]);

        $response = $this->actingAs($employee, 'web')
            ->getJson(route('vendor.showroom.show'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $showroom->id)
            ->assertJsonPath('data.name', 'معرض القمة');
    }

    public function test_vendor_can_view_active_contract_and_limits(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR]);
        $showroom = Showroom::factory()->create(['user_id' => $owner->id]);

        $contract = ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'contract_number' => 'CNT-2026-9999',
            'started_at' => now()->toDateString(),
            'end_at' => now()->addDays(30)->toDateString(),
            'max_rooms' => 50,
            'max_packages' => 15,
            'amount' => 15000.00,
            'status' => ContractStatus::ACTIVE,
        ]);

        $response = $this->actingAs($owner, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->getJson(route('vendor.showroom.contract'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonPath('data.contractNumber', 'CNT-2026-9999')
            ->assertJsonPath('data.maxRooms', 50)
            ->assertJsonPath('data.maxPackages', 15)
            ->assertJsonPath('data.status.label', 'ساري')
            ->assertJsonPath('data.isExpired', false);
    }

    public function test_vendor_can_apply_for_blue_badge_verification(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR]);
        $showroom = Showroom::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.showroom.verification.apply'), [
                'month' => 6,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تقديم طلب توثيق المعرض بنجاح وهو قيد المراجعة.')
            ->assertJsonPath('data.month', 6)
            ->assertJsonPath('data.monthlyPrice', 500)
            ->assertJsonPath('data.totalAmount', 3000)
            ->assertJsonPath('data.status.value', VerificationStatus::PENDING->value)
            ->assertJsonPath('data.status.label', 'قيد المراجعة')
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('showrooms_verification', [
            'showrooms_id' => $showroom->id,
            'month' => 6,
            'monthly_price' => 500.00,
            'total_amount' => 3000.00,
            'status' => VerificationStatus::PENDING->value,
        ]);
    }

    public function test_vendor_cannot_apply_for_duplicate_pending_verification(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR]);
        $showroom = Showroom::factory()->create(['user_id' => $owner->id]);

        ShowroomVerification::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => VerificationStatus::PENDING,
        ]);

        $response = $this->actingAs($owner, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('vendor.showroom.verification.apply'), [
                'month' => 3,
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/showroom.errors.pending_verification_exists'));
    }

    public function test_vendor_without_showroom_receives_not_found_error(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR]);

        $response = $this->actingAs($owner, 'web')
            ->withHeader('Accept-Language', 'ar')
            ->getJson(route('vendor.showroom.show'));

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'المعرض غير موجود.');
    }

    public function test_vendor_can_view_their_showroom_branches(): void
    {
        $owner = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $showroom = Showroom::factory()->create(['user_id' => $owner->id]);

        ShowroomBranch::factory()->count(2)->create([
            'showrooms_id' => $showroom->id,
        ]);

        $response = $this->actingAs($owner, 'web')
            ->getJson(route('vendor.showroom.branches'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }
}
