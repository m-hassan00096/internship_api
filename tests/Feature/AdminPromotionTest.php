<?php

namespace Tests\Feature;

use App\Enums\PromotionStatus;
use App\Enums\UserScope;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserScope::ADMIN]);
    }

    public function test_unauthenticated_user_cannot_access_admin_promotions(): void
    {
        $this->getJson(route('admin.promotions.active'))->assertUnauthorized();
        $this->getJson(route('admin.promotions.requests'))->assertUnauthorized();
        $this->getJson(route('admin.promotions.pricing'))->assertUnauthorized();
        $this->putJson(route('admin.promotions.pricing.update'), ['prices' => []])->assertUnauthorized();
        $this->getJson(route('admin.promotions.show', 1))->assertUnauthorized();
        $this->patchJson(route('admin.promotions.update-status', 1), ['status' => 2])->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_admin_promotions(): void
    {
        $vendor = User::factory()->create(['role' => UserScope::VENDOR]);
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this
            ->actingAs($vendor, 'web')
            ->getJson(route('admin.promotions.active'))
            ->assertForbidden();

        $this
            ->actingAs($client, 'web')
            ->getJson(route('admin.promotions.active'))
            ->assertForbidden();
    }

    public function test_admin_can_list_active_promotions_with_kpi_metrics(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'Track Furniture']);
        $type = PromotionType::factory()->create(['placement' => 'room', 'daily_price' => 200]);
        $room = Room::factory()->create(['showrooms_id' => $showroom->id, 'name' => 'Master Suite Modern']);

        // 2 Active promotions
        Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'promotion_type_id' => $type->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
            'days' => 7,
            'daily_price' => 200,
            'total_amount' => 1400,
        ]);

        Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'promotion_type_id' => $type->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(12),
            'days' => 13,
            'daily_price' => 200,
            'total_amount' => 2600,
        ]);

        // 1 Pending promotion (should not appear in active list)
        Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => PromotionStatus::PENDING,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.active'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.activeCount', 2)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'kpis' => ['activeCount', 'activeCountFormatted'],
                    'items' => [
                        '*' => [
                            'id',
                            'type',
                            'content' => ['name', 'code', 'image'],
                            'showroom',
                            'startsAt',
                            'endsAt',
                            'remainingDays',
                            'dailyPrice',
                            'totalAmount',
                            'status' => ['value', 'label', 'badgeColor'],
                        ],
                    ],
                    'pagination' => ['currentPage', 'lastPage', 'perPage', 'total'],
                    'ruleNote' => ['title', 'note'],
                ],
            ]);
    }

    public function test_admin_can_filter_active_promotions_by_search_and_type(): void
    {
        $showroom1 = Showroom::factory()->create(['name' => 'Track Furniture Special']);
        $showroom2 = Showroom::factory()->create(['name' => 'Classic Home']);
        $typeRoom = PromotionType::factory()->create(['placement' => 'room']);
        $typeBanner = PromotionType::factory()->create(['placement' => 'banner']);

        Promotion::factory()->create([
            'showrooms_id' => $showroom1->id,
            'promotion_type_id' => $typeRoom->id,
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        Promotion::factory()->create([
            'showrooms_id' => $showroom2->id,
            'promotion_type_id' => $typeBanner->id,
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        // Search for Track Furniture
        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.active', ['search' => 'Track Furniture']));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.showroom', 'Track Furniture Special');

        // Filter by placement type
        $responseType = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.active', ['type' => 'banner']));

        $responseType
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.showroom', 'Classic Home');
    }

    public function test_admin_can_list_promotion_requests_with_pending_kpi(): void
    {
        $showroom = Showroom::factory()->create();
        $type = PromotionType::factory()->create(['placement' => 'room', 'daily_price' => 250]);
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        // 3 Pending requests
        Promotion::factory()->count(3)->create([
            'showrooms_id' => $showroom->id,
            'promotion_type_id' => $type->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'days' => 7,
            'daily_price' => 250,
            'total_amount' => 1750,
            'status' => PromotionStatus::PENDING,
        ]);

        // 1 Active promotion
        Promotion::factory()->create(['status' => PromotionStatus::ACTIVE]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.requests'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.pendingCount', 3)
            ->assertJsonCount(3, 'data.items')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'kpis' => ['pendingCount', 'pendingCountFormatted'],
                    'items' => [
                        '*' => [
                            'id',
                            'type',
                            'content' => ['name', 'code', 'image'],
                            'showroom',
                            'duration',
                            'dailyPrice',
                            'totalAmount',
                            'requestDate',
                            'status' => ['value', 'label', 'badgeColor'],
                        ],
                    ],
                    'pagination',
                    'ruleNote',
                ],
            ]);
    }

    public function test_admin_can_view_promotion_details_for_modal(): void
    {
        $showroom = Showroom::factory()->create(['name' => 'Track Furniture']);
        $type = PromotionType::factory()->create(['placement' => 'room', 'daily_price' => 250]);
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'غرفة نوم مودرن Master Suite',
            'sku' => 'RM-0281',
        ]);

        $promotion = Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'promotion_type_id' => $type->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'days' => 7,
            'daily_price' => 250.0,
            'total_amount' => 1750.0,
            'status' => PromotionStatus::PENDING,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.show', $promotion->id), ['Accept-Language' => 'ar']);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $promotion->id)
            ->assertJsonPath('data.refCode', '#PRM-REQ-'.$promotion->id)
            ->assertJsonPath('data.targetEntity.type', 'room')
            ->assertJsonPath('data.targetEntity.name', 'غرفة نوم مودرن Master Suite')
            ->assertJsonPath('data.targetEntity.sku', 'SKU: RM-0281')
            ->assertJsonPath('data.showroom.name', 'Track Furniture')
            ->assertJsonPath('data.showroom.initials', 'TF')
            ->assertJsonPath('data.pricing.duration', '7 أيام')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'refCode',
                    'requestDate',
                    'status' => ['value', 'label', 'badgeColor'],
                    'targetEntity' => ['type', 'typeLabel', 'id', 'name', 'sku', 'image'],
                    'showroom' => ['id', 'name', 'code', 'logo', 'initials'],
                    'pricing' => ['duration', 'dailyPrice', 'totalAmount', 'durationNote', 'dailyPriceNote', 'totalAmountNote', 'priceLockNote'],
                    'operationalRule' => ['title', 'text'],
                    'moderation' => ['reviewedBy', 'reviewedAt', 'rejectReason'],
                ],
            ]);
    }

    public function test_admin_can_approve_and_start_promotion_instantly(): void
    {
        $showroom = Showroom::factory()->create();
        $promotion = Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'days' => 7,
            'status' => PromotionStatus::PENDING,
            'starts_at' => null,
            'ends_at' => null,
            'reviewed_by' => null,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.promotions.update-status', $promotion->id), [
                'status' => PromotionStatus::ACTIVE->value,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', PromotionStatus::ACTIVE->value);

        $fresh = $promotion->fresh();
        $this->assertEquals(PromotionStatus::ACTIVE, $fresh->status);
        $this->assertNotNull($fresh->starts_at);
        $this->assertNotNull($fresh->ends_at);
        $this->assertEquals(7, (int) round($fresh->starts_at->diffInDays($fresh->ends_at)));
        $this->assertEquals($this->admin->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_admin_can_reject_promotion_with_reason(): void
    {
        $showroom = Showroom::factory()->create();
        $promotion = Promotion::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => PromotionStatus::PENDING,
        ]);

        $reason = 'الصور غير مطابقة للمواصفات الفنية المعتمدة للمنصة.';

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.promotions.update-status', $promotion->id), [
                'status' => PromotionStatus::REJECTED->value,
                'reject_reason' => $reason,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', PromotionStatus::REJECTED->value)
            ->assertJsonPath('data.moderation.rejectReason', $reason);

        $fresh = $promotion->fresh();
        $this->assertEquals(PromotionStatus::REJECTED, $fresh->status);
        $this->assertEquals($reason, $fresh->reject_reason);
        $this->assertEquals($this->admin->id, $fresh->reviewed_by);
    }

    public function test_rejecting_promotion_without_reason_fails_validation(): void
    {
        $promotion = Promotion::factory()->create(['status' => PromotionStatus::PENDING]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.promotions.update-status', $promotion->id), [
                'status' => PromotionStatus::REJECTED->value,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_status_value_fails_validation(): void
    {
        $promotion = Promotion::factory()->create();

        $response = $this
            ->actingAs($this->admin, 'web')
            ->patchJson(route('admin.promotions.update-status', $promotion->id), [
                'status' => 99,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('admin/promotion.errors.status_invalid'));
    }

    public function test_admin_can_get_pricing_settings(): void
    {
        PromotionType::factory()->create([
            'placement' => 'room',
            'daily_price' => 200,
        ]);
        PromotionType::factory()->create([
            'placement' => 'banner',
            'daily_price' => 500,
        ]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->getJson(route('admin.promotions.pricing'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'types' => [
                        '*' => [
                            'id',
                            'placement',
                            'title',
                            'description',
                            'dailyPrice',
                        ],
                    ],
                    'lastUpdated',
                    'protectionRule' => [
                        'badge',
                        'title',
                        'description',
                        'example' => ['title', 'text'],
                    ],
                    'channels' => [
                        'badge',
                        'title',
                        'items' => [
                            '*' => ['title', 'description'],
                        ],
                        'note',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_promotion_pricing(): void
    {
        $type1 = PromotionType::factory()->create(['placement' => 'room', 'daily_price' => 200]);
        $type2 = PromotionType::factory()->create(['placement' => 'banner', 'daily_price' => 500]);

        $response = $this
            ->actingAs($this->admin, 'web')
            ->putJson(route('admin.promotions.pricing.update'), [
                'prices' => [
                    ['id' => $type1->id, 'dailyPrice' => 300],
                    ['id' => $type2->id, 'dailyPrice' => 750],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(300.0, (float) $type1->fresh()->daily_price);
        $this->assertEquals(750.0, (float) $type2->fresh()->daily_price);
    }
}
