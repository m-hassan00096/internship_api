<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomImage>
 */
class RoomImageFactory extends Factory
{
    protected $model = RoomImage::class;

    public function definition(): array
    {
        return [
            'rooms_id' => Room::factory(),
            'image_path' => 'rooms/images/'.fake()->uuid().'.jpg',
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
        ]);
    }
}
