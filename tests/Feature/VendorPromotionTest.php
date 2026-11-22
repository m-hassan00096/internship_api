<?php

namespace Tests\Feature;

use App\Enums\PromotionStatus;
use App\Enums\RoomStatus;
use App\Enums\UserScope;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPromotionTest extends TestCase
{
    use RefreshDatabase;

    private function createVendorWithShowroom(): array
    {
        $user = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $showroom = Showroom::factory()->create(['user_id' => $user->id]);

        return [$user, $showroom];
    }

    public function test_unauthenticated_user_cannot_access_promotion_endpoints(): void
    {
        $this->getJson(route('vendor.promotion-types.index'))->assertUnauthorized();
        $this->getJson(route('vendor.promotions.index'))->assertUnauthorized();
        $this->postJson(route('vendor.promotions.store'), [])->assertUnauthorized();
    }

    public function test_non_vendor_cannot_access_promotion_endpoints(): void
    {
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this->actingAs($client, 'web')
            ->getJson(route('vendor.promotion-types.index'))
            ->assertForbidden();
    }

    public function test_vendor_can_list_active_promotion_types(): void
    {
        [$user] = $this->createVendorWithShowroom();

        $activeType = PromotionType::factory()->create([
            'name' => ['ar' => 'بانر رئيسي', 'en' => 'Main Banner'],
            'placement' => 'banner',
            'daily_price' => 120.00,
            'is_active' => true,
        ]);
        PromotionType::factory()->inactive()->create();

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.promotion-types.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeType->id)
            ->assertJsonPath('data.0.dailyPrice', 120);
    }

    public function test_vendor_can_create_promotion_request_with_automatic_calculation(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => RoomStatus::PUBLISHED,
        ]);

        $type = PromotionType::factory()->create([
            'daily_price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.promotions.store'), [
                'promotableType' => 'room',
                'promotableId' => $room->id,
                'promotionTypeId' => $type->id,
                'days' => 5,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.promotableType', 'room')
            ->assertJsonPath('data.promotableId', $room->id)
            ->assertJsonPath('data.days', 5)
            ->assertJsonPath('data.dailyPrice', 150)
            ->assertJsonPath('data.totalAmount', 750)
            ->assertJsonPath('data.status.value', PromotionStatus::PENDING->value);

        $this->assertDatabaseHas('promotions', [
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'promotion_type_id' => $type->id,
            'days' => 5,
            'daily_price' => 150.00,
            'total_amount' => 750.00,
            'status' => PromotionStatus::PENDING->value,
            'created_by' => $user->id,
        ]);
    }

    public function test_vendor_cannot_promote_another_vendors_room(): void
    {
        [$user] = $this->createVendorWithShowroom();
        [, $otherShowroom] = $this->createVendorWithShowroom();

        $otherRoom = Room::factory()->create([
            'showrooms_id' => $otherShowroom->id,
            'status' => RoomStatus::PUBLISHED,
        ]);

        $type = PromotionType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.promotions.store'), [
                'promotableType' => 'room',
                'promotableId' => $otherRoom->id,
                'promotionTypeId' => $type->id,
                'days' => 3,
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/promotion.errors.promotable_not_found'));
    }

    public function test_vendor_cannot_promote_draft_room(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $draftRoom = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => RoomStatus::DRAFT,
        ]);

        $type = PromotionType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.promotions.store'), [
                'promotableType' => 'room',
                'promotableId' => $draftRoom->id,
                'promotionTypeId' => $type->id,
                'days' => 3,
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/promotion.errors.promotable_not_published'));
    }

    public function test_vendor_cannot_create_duplicate_pending_promotion_in_same_placement(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => RoomStatus::PUBLISHED,
        ]);
        $type = PromotionType::factory()->create(['is_active' => true]);

        Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'promotion_type_id' => $type->id,
            'status' => PromotionStatus::PENDING,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.promotions.store'), [
                'promotableType' => 'room',
                'promotableId' => $room->id,
                'promotionTypeId' => $type->id,
                'days' => 4,
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/promotion.errors.duplicate_pending'));
    }

    public function test_vendor_can_list_their_promotions(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();

        Promotion::factory()->count(3)->create([
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.promotions.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_vendor_can_view_single_promotion_details(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $promotion = Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'days' => 7,
            'daily_price' => 100,
            'total_amount' => 700,
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.promotions.show', $promotion->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $promotion->id)
            ->assertJsonPath('data.totalAmount', 700);
    }

    public function test_vendor_can_cancel_pending_promotion(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $promotion = Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => PromotionStatus::PENDING,
        ]);

        $response = $this->actingAs($user, 'web')
            ->deleteJson(route('vendor.promotions.destroy', $promotion->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('vendor/promotion.messages.cancelled'));

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_vendor_cannot_cancel_approved_or_active_promotion(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $promotion = Promotion::factory()->active()->create([
            'showrooms_id' => $showroom->id,
        ]);

        $response = $this->actingAs($user, 'web')
            ->deleteJson(route('vendor.promotions.destroy', $promotion->id));

        $response->assertBadRequest()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/promotion.errors.cannot_cancel'));

        $this->assertDatabaseHas('promotions', ['id' => $promotion->id]);
    }
}
