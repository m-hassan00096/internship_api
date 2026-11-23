<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewReply>
 */
class ReviewReplyFactory extends Factory
{
    protected $model = ReviewReply::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reviews_id' => Review::factory(),
            'user_id' => User::factory(),
            'reply' => fake()->paragraph(),
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
            'reject_reason' => 'الرد غير ملائم ومخالف لسياسة المتجر.',
        ]);
    }
}
