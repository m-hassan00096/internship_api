<?php

namespace Database\Factories;

use App\Models\Showroom;
use App\Models\ShowroomBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShowroomBranch>
 */
class ShowroomBranchFactory extends Factory
{
    protected $model = ShowroomBranch::class;

    public function definition(): array
    {
        return [
            'showrooms_id' => Showroom::factory(),
            'name' => 'فرع '.fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'phone' => '010'.fake()->numerify('########'),
            'whatsapp' => '010'.fake()->numerify('########'),
            'is_main' => false,
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => [
            'is_main' => true,
        ]);
    }
}
