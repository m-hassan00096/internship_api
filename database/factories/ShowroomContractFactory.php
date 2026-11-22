<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShowroomContract>
 */
class ShowroomContractFactory extends Factory
{
    protected $model = ShowroomContract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'showrooms_id' => Showroom::factory(),
            'contract_number' => 'CNT-'.fake()->unique()->numerify('2026-####'),
            'started_at' => now()->toDateString(),
            'end_at' => now()->addYear()->toDateString(),
            'amount' => 12000.00,
            'max_rooms' => 30,
            'max_packages' => 10,
            'contract_file' => null,
            'created_by' => User::factory(),
            'status' => ContractStatus::ACTIVE,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'started_at' => now()->subYears(2)->toDateString(),
            'end_at' => now()->subYear()->toDateString(),
            'status' => ContractStatus::EXPIRED,
        ]);
    }
}
