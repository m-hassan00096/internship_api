<?php

namespace Database\Factories;

use App\Enums\PromotionStatus;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = fake()->numberBetween(3, 30);
        $dailyPrice = fake()->randomFloat(2, 50, 300);

        return [
            'showrooms_id' => Showroom::factory(),
            'promotable_type' => 'room',
            'promotable_id' => Room::factory(),
            'promotion_type_id' => PromotionType::factory(),
            'days' => $days,
            'daily_price' => $dailyPrice,
            'total_amount' => round($days * $dailyPrice, 2),
            'status' => PromotionStatus::PENDING,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::APPROVED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::REJECTED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'reject_reason' => 'الصور غير واضحة ولا تطابق معايير الترويج.',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromotionStatus::EXPIRED,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }
}
