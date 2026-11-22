<?php

namespace Tests\Feature;

use App\Enums\PromotionStatus;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_promotion_type_and_scope_active(): void
    {
        $activeType = PromotionType::factory()->create([
            'name' => ['ar' => 'بانر رئيسي', 'en' => 'Main Banner'],
            'placement' => 'banner',
            'daily_price' => 150.00,
            'is_active' => true,
        ]);

        $inactiveType = PromotionType::factory()->inactive()->create();

        $this->assertDatabaseHas('promotion_types', [
            'id' => $activeType->id,
            'placement' => 'banner',
            'is_active' => true,
        ]);

        $this->assertSame('بانر رئيسي', $activeType->localizedName('ar'));
        $this->assertSame('Main Banner', $activeType->localizedName('en'));
        $this->assertTrue(PromotionType::active()->get()->contains($activeType));
        $this->assertFalse(PromotionType::active()->get()->contains($inactiveType));
    }

    public function test_can_create_promotion_with_polymorphic_relation(): void
    {
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);
        $type = PromotionType::factory()->create(['daily_price' => 100.00]);
        $creator = User::factory()->create();

        $promotion = Promotion::create([
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'promotion_type_id' => $type->id,
            'days' => 5,
            'daily_price' => 100.00,
            'total_amount' => 500.00,
            'status' => PromotionStatus::PENDING,
            'created_by' => $creator->id,
        ]);

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'showrooms_id' => $showroom->id,
            'promotable_type' => 'room',
            'promotable_id' => $room->id,
            'days' => 5,
            'total_amount' => 500.00,
            'status' => PromotionStatus::PENDING->value,
        ]);

        $this->assertTrue($promotion->showroom->is($showroom));
        $this->assertTrue($promotion->type->is($type));
        $this->assertTrue($promotion->promotable->is($room));
        $this->assertTrue($promotion->creator->is($creator));
        $this->assertTrue($showroom->promotions->contains($promotion));
        $this->assertTrue($room->promotions->contains($promotion));
    }

    public function test_promotion_status_and_scopes(): void
    {
        $activePromotion = Promotion::factory()->active()->create();
        $pendingPromotion = Promotion::factory()->create(['status' => PromotionStatus::PENDING]);
        $expiredPromotion = Promotion::factory()->expired()->create();

        $this->assertTrue($activePromotion->isActive());
        $this->assertTrue($pendingPromotion->isPending());
        $this->assertTrue($expiredPromotion->isExpired());

        $this->assertTrue(Promotion::active()->get()->contains($activePromotion));
        $this->assertFalse(Promotion::active()->get()->contains($pendingPromotion));
        $this->assertFalse(Promotion::active()->get()->contains($expiredPromotion));

        $this->assertTrue(Promotion::pending()->get()->contains($pendingPromotion));
        $this->assertFalse(Promotion::pending()->get()->contains($activePromotion));
    }

    public function test_promotion_status_labels_in_arabic_and_english(): void
    {
        app()->setLocale('ar');
        $this->assertSame('قيد المراجعة', PromotionStatus::PENDING->label());
        $this->assertSame('تمت الموافقة', PromotionStatus::APPROVED->label());
        $this->assertSame('نشط وساري', PromotionStatus::ACTIVE->label());
        $this->assertSame('منتهي', PromotionStatus::EXPIRED->label());
        $this->assertSame('مرفوض', PromotionStatus::REJECTED->label());

        app()->setLocale('en');
        $this->assertSame('Pending Review', PromotionStatus::PENDING->label());
        $this->assertSame('Approved', PromotionStatus::APPROVED->label());
        $this->assertSame('Active', PromotionStatus::ACTIVE->label());
        $this->assertSame('Expired', PromotionStatus::EXPIRED->label());
        $this->assertSame('Rejected', PromotionStatus::REJECTED->label());
    }
}
