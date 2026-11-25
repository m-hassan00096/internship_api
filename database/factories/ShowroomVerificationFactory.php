<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\Showroom;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShowroomVerification>
 */
class ShowroomVerificationFactory extends Factory
{
    protected $model = ShowroomVerification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'showrooms_id' => Showroom::factory(),
            'month' => 6,
            'monthly_price' => 500.00,
            'total_amount' => 3000.00,
            'status' => VerificationStatus::PENDING,
            'start_at' => null,
            'end_at' => null,
            'review_by' => null,
            'review_at' => null,
            'reject_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VerificationStatus::APPROVED,
            'start_at' => now(),
            'end_at' => now()->addMonths(6),
            'review_by' => User::factory(),
            'review_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VerificationStatus::REJECTED,
            'review_by' => User::factory(),
            'review_at' => now(),
            'reject_reason' => 'مستندات التوثيق غير واضحة.',
        ]);
    }
}
