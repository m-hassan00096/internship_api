<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\DiscountType;
use App\Enums\RoomAvailabilityStatus;
use App\Enums\UserScope;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorRoomTest extends TestCase
{
    use RefreshDatabase;

    private function createVendorWithShowroom(): array
    {
        $user = User::factory()->create([
            'role' => UserScope::VENDOR,
            'manager_id' => null,
        ]);

        $showroom = Showroom::factory()->create([
            'user_id' => $user->id,
        ]);

        $contract = ShowroomContract::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => ContractStatus::ACTIVE,
            'started_at' => now()->startOfYear(),
            'end_at' => now()->addYear(),
            'max_rooms' => 5,
        ]);

        return [$user, $showroom, $contract];
    }

    public function test_can_list_active_categories(): void
    {
        Category::factory()->create(['name' => ['ar' => 'غرف معيشة', 'en' => 'Living Rooms'], 'is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson(route('vendor.categories.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_vendor_can_list_their_rooms(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $category = Category::factory()->create();

        Room::factory()->count(3)->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $category->id,
        ]);

        // Room for another showroom
        $otherShowroom = Showroom::factory()->create();
        Room::factory()->create(['showrooms_id' => $otherShowroom->id]);

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.rooms.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_vendor_can_create_a_room(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'name' => 'غرفة نوم ملكية مودرن',
            'sku' => 'ROYAL-BED-01',
            'warranty' => '3 سنوات ضمان',
            'description' => 'غرفة نوم مصنوعة من خشب الزان الطبيعي.',
            'price' => 25000.00,
            'discount_type' => DiscountType::PERCENTAGE->value,
            'discount_value' => 10.00,
            'availability_status' => RoomAvailabilityStatus::AVAILABLE->value,
            'preparation_days' => 10,
        ];

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.rooms.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'غرفة نوم ملكية مودرن')
            ->assertJsonPath('data.status.name', 'DRAFT')
            ->assertJsonPath('data.price', 25000)
            ->assertJsonPath('data.finalPrice', 22500);

        $this->assertDatabaseHas('rooms', [
            'showrooms_id' => $showroom->id,
            'name' => 'غرفة نوم ملكية مودرن',
            'sku' => 'ROYAL-BED-01',
        ]);
    }

    public function test_cannot_create_room_if_contract_limit_reached(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $category = Category::factory()->create();

        // Create 5 rooms (contract limit is 5)
        Room::factory()->count(5)->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $category->id,
        ]);

        $payload = [
            'category_id' => $category->id,
            'name' => 'غرفة سادسة تتجاوز الحد',
            'price' => 10000.00,
            'availability_status' => RoomAvailabilityStatus::AVAILABLE->value,
        ];

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.rooms.store'), $payload);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('vendor/room.errors.limit_reached'));
    }

    public function test_vendor_can_view_single_room(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $category = Category::factory()->create();

        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'category_id' => $category->id,
            'name' => 'غرفة أطفال كلاسيك',
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.rooms.show', $room->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.name', 'غرفة أطفال كلاسيك');
    }

    public function test_vendor_cannot_view_another_showroom_room(): void
    {
        [$user] = $this->createVendorWithShowroom();
        $otherShowroom = Showroom::factory()->create();
        $otherRoom = Room::factory()->create(['showrooms_id' => $otherShowroom->id]);

        $response = $this->actingAs($user, 'web')
            ->getJson(route('vendor.rooms.show', $otherRoom->id));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_can_update_their_room(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'الاسم القديم',
        ]);

        $response = $this->actingAs($user, 'web')
            ->putJson(route('vendor.rooms.update', $room->id), [
                'name' => 'الاسم الجديد المحدث',
                'price' => 18000.00,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'الاسم الجديد المحدث');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'الاسم الجديد المحدث',
        ]);
    }

    public function test_vendor_can_delete_their_room(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $response = $this->actingAs($user, 'web')
            ->deleteJson(route('vendor.rooms.destroy', $room->id));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_vendor_can_upload_images_and_set_primary(): void
    {
        Storage::fake('public');
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $file1 = UploadedFile::fake()->create('room1.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('room2.jpg', 100, 'image/jpeg');

        $uploadResponse = $this->actingAs($user, 'web')
            ->postJson(route('vendor.rooms.images.upload', $room->id), [
                'images' => [$file1, $file2],
            ]);

        $uploadResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $images = RoomImage::where('rooms_id', $room->id)->get();
        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary); // first is primary automatically

        // Set second image as primary
        $primaryResponse = $this->actingAs($user, 'web')
            ->patchJson(route('vendor.rooms.images.primary', [$room->id, $images[1]->id]));

        $primaryResponse->assertOk()
            ->assertJsonPath('data.isPrimary', true);

        $this->assertFalse($images[0]->fresh()->is_primary);
        $this->assertTrue($images[1]->fresh()->is_primary);
    }

    public function test_vendor_can_manage_room_components(): void
    {
        [$user, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $payload = [
            'name' => 'سرير رئيسي كينج',
            'quantity' => 1,
            'description' => 'سرير بمقاس 180×200 سم خشب زان.',
            'attributes' => [
                ['name' => 'الطول', 'value' => '200', 'unit' => 'سم'],
                ['name' => 'العرض', 'value' => '180', 'unit' => 'سم'],
            ],
        ];

        $response = $this->actingAs($user, 'web')
            ->postJson(route('vendor.rooms.components.store', $room->id), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'سرير رئيسي كينج')
            ->assertJsonCount(2, 'data.attributes');

        $componentId = $response->json('data.id');

        // Delete component
        $deleteResponse = $this->actingAs($user, 'web')
            ->deleteJson(route('vendor.rooms.components.destroy', [$room->id, $componentId]));

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('room_components', ['id' => $componentId]);
    }
}
