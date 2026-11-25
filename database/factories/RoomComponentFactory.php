<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomComponent>
 */
class RoomComponentFactory extends Factory
{
    protected $model = RoomComponent::class;

    public function definition(): array
    {
        return [
            'rooms_id' => Room::factory(),
            'name' => fake()->word(),
            'quantity' => 1,
            'image_path' => null,
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
