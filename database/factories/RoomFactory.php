<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
use App\Models\Category;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'showrooms_id' => Showroom::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
            'warranty' => 'ضمان لمدة عامين ضد عيوب الصناعة',
            'description' => fake()->paragraph(),
            'price' => 12500.00,
            'discount_type' => null,
            'discount_value' => null,
            'availability_status' => RoomAvailabilityStatus::AVAILABLE,
            'preparation_days' => 14,
            'status' => RoomStatus::DRAFT,
            'current_step' => 1,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => RoomStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function withDiscount(DiscountType $type = DiscountType::PERCENTAGE, float $value = 10.0): static
    {
        return $this->state(fn () => [
            'discount_type' => $type,
            'discount_value' => $value,
        ]);
    }
}
