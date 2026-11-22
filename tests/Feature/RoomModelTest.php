<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\RoomStatus;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomComponent;
use App\Models\RoomComponentAttribute;
use App\Models\RoomImage;
use App\Models\Showroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_room_with_relationships(): void
    {
        $showroom = Showroom::factory()->create();
        $category = Category::factory()->create();

        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $category->id,
            'status' => RoomStatus::PUBLISHED,
            'availability_status' => RoomAvailabilityStatus::AVAILABLE,
        ]);

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertTrue($room->showroom->is($showroom));
        $this->assertTrue($room->category->is($category));
        $this->assertTrue($showroom->rooms->contains($room));
        $this->assertTrue($category->rooms->contains($room));
        $this->assertTrue($room->isPublished());
    }

    public function test_room_images_and_primary_image_relationship(): void
    {
        $room = Room::factory()->create();

        $secondaryImage = RoomImage::factory()->create([
            'rooms_id' => $room->id,
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $primaryImage = RoomImage::factory()->primary()->create([
            'rooms_id' => $room->id,
            'sort_order' => 1,
        ]);

        $this->assertCount(2, $room->images);
        $this->assertTrue($room->primaryImage->is($primaryImage));
        $this->assertFalse($secondaryImage->is_primary);
    }

    public function test_room_components_and_attributes_hierarchy(): void
    {
        $room = Room::factory()->create();

        $component = RoomComponent::factory()->create([
            'rooms_id' => $room->id,
            'name' => 'سرير',
            'quantity' => 1,
        ]);

        $attrLength = RoomComponentAttribute::factory()->create([
            'room_component_id' => $component->id,
            'name' => 'الطول',
            'value' => '200',
            'unit' => 'سم',
        ]);

        $attrWidth = RoomComponentAttribute::factory()->create([
            'room_component_id' => $component->id,
            'name' => 'العرض',
            'value' => '160',
            'unit' => 'سم',
        ]);

        $this->assertCount(1, $room->components);
        $this->assertCount(2, $component->attributes);
        $this->assertTrue($attrLength->component->is($component));
        $this->assertTrue($attrWidth->component->is($component));
    }

    public function test_category_localization_and_active_scope(): void
    {
        $category = Category::factory()->create([
            'name' => ['ar' => 'غرف نوم', 'en' => 'Bedrooms'],
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $this->assertEquals('غرف نوم', $category->localizedName('ar'));
        $this->assertEquals('Bedrooms', $category->localizedName('en'));
        $this->assertTrue(Category::active()->get()->contains($category));
        $this->assertFalse(Category::active()->get()->contains($inactiveCategory));
    }

    public function test_room_discount_calculations(): void
    {
        $roomWithoutDiscount = Room::factory()->create([
            'price' => 10000.00,
            'discount_type' => null,
            'discount_value' => null,
        ]);

        $this->assertFalse($roomWithoutDiscount->hasDiscount());
        $this->assertEquals(0.0, $roomWithoutDiscount->discountAmount());
        $this->assertEquals(10000.00, $roomWithoutDiscount->finalPrice());

        // Percentage discount 20% of 10000 = 2000 => final = 8000
        $roomPercentage = Room::factory()->create([
            'price' => 10000.00,
            'discount_type' => DiscountType::PERCENTAGE,
            'discount_value' => 20.00,
        ]);

        $this->assertTrue($roomPercentage->hasDiscount());
        $this->assertEquals(2000.00, $roomPercentage->discountAmount());
        $this->assertEquals(8000.00, $roomPercentage->finalPrice());

        // Fixed discount 1500 of 10000 = 1500 => final = 8500
        $roomFixed = Room::factory()->create([
            'price' => 10000.00,
            'discount_type' => DiscountType::FIXED,
            'discount_value' => 1500.00,
        ]);

        $this->assertTrue($roomFixed->hasDiscount());
        $this->assertEquals(1500.00, $roomFixed->discountAmount());
        $this->assertEquals(8500.00, $roomFixed->finalPrice());
    }
}
