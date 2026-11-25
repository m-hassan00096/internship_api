<?php

namespace Database\Factories;

use App\Models\RoomComponent;
use App\Models\RoomComponentAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomComponentAttribute>
 */
class RoomComponentAttributeFactory extends Factory
{
    protected $model = RoomComponentAttribute::class;

    public function definition(): array
    {
        return [
            'room_component_id' => RoomComponent::factory(),
            'name' => 'الطول',
            'value' => '200',
            'unit' => 'سم',
            'sort_order' => 0,
        ];
    }
}
