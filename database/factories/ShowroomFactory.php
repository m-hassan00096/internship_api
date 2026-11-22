<?php

namespace Database\Factories;

use App\Enums\ShowroomStatus;
use App\Models\BusinessType;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showroom>
 */
class ShowroomFactory extends Factory
{
    protected $model = Showroom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory(),
            'business_type_id' => BusinessType::factory(),
            'name' => $name,
            'slug' => fake()->unique()->slug(),
            'bio' => fake()->paragraph(),
            'logo' => null,
            'cover_image' => null,
            'phone' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'status' => ShowroomStatus::ACTIVE,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShowroomStatus::SUSPENDED,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShowroomStatus::DRAFT,
        ]);
    }
}
