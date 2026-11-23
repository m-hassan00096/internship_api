<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'showrooms_id' => Showroom::factory(),
            'reviewable_type' => 'room',
            'reviewable_id' => Room::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
            'status' => ReviewStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::APPROVED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::REJECTED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'reject_reason' => 'محتوى التقييم غير لائق أو مخالف للشروط.',
        ]);
    }

    public function forShowroom(Showroom $showroom): static
    {
        return $this->state(fn (array $attributes) => [
            'showrooms_id' => $showroom->id,
            'reviewable_type' => 'showroom',
            'reviewable_id' => $showroom->id,
        ]);
    }
}
