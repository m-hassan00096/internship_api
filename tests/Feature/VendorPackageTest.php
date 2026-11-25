<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Enums\UserScope;
use App\Models\Package;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorPackageTest extends TestCase
{
    use RefreshDatabase;

    private function createVendorWithShowroom(): array
    {
        $user = User::factory()->create(['role' => UserScope::VENDOR, 'manager_id' => null]);
        $showroom = Showroom::factory()->create(['user_id' => $user->id]);

        return [$user, $showroom];
    }

    public function test_unauthenticated_user_cannot_access_package_endpoints(): void
    {
        $this->getJson(route('vendor.packages.index'))->assertUnauthorized();
        $this->postJson(route('vendor.packages.store'), [])->assertUnauthorized();
        $this->getJson(route('vendor.packages.show', ['package' => 1]))->assertUnauthorized();
    }

    public function test_non_vendor_cannot_access_package_endpoints(): void
    {
        $client = User::factory()->create(['role' => UserScope::CLIENT]);

        $this->actingAs($client, 'web')
            ->getJson(route('vendor.packages.index'))
            ->assertForbidden();
    }

    public function test_vendor_can_list_their_showroom_packages(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $package1 = Package::factory()->published()->create(['showrooms_id' => $showroom->id]);
        $package1->rooms()->attach($room->id);

        $package2 = Package::factory()->create(['showrooms_id' => $showroom->id]);
        $package2->rooms()->attach($room->id);

        // Other showroom's package
        $otherShowroom = Showroom::factory()->create();
        Package::factory()->create(['showrooms_id' => $otherShowroom->id]);

        $response = $this->actingAs($vendor, 'web')
            ->getJson(route('vendor.packages.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_vendor_can_create_package(): void
    {
        Storage::fake('public');
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $room1 = Room::factory()->create(['showrooms_id' => $showroom->id, 'price' => 30000]);
        $room2 = Room::factory()->create(['showrooms_id' => $showroom->id, 'price' => 40000]);

        $image = UploadedFile::fake()->create('package.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.packages.store'), [
                'name' => 'باكدج شقة العروسة سوبر لوكس',
                'sku' => 'PKG-BRIDE-01',
                'description' => 'عرض خاص يشمل غرفة نوم ماستر وغرفة معيشة بخصم هائل',
                'price' => 60000.00,
                'status' => PackageStatus::PUBLISHED->value,
                'image' => $image,
                'rooms' => [
                    ['id' => $room1->id, 'sort_order' => 1],
                    ['id' => $room2->id, 'sort_order' => 2],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'باكدج شقة العروسة سوبر لوكس')
            ->assertJsonPath('data.price', 60000)
            ->assertJsonPath('data.totalRoomsPrice', 70000)
            ->assertJsonCount(2, 'data.rooms');

        $this->assertDatabaseHas('packages', [
            'showrooms_id' => $showroom->id,
            'name' => 'باكدج شقة العروسة سوبر لوكس',
            'sku' => 'PKG-BRIDE-01',
            'price' => 60000.00,
            'status' => PackageStatus::PUBLISHED->value,
        ]);

        $package = Package::where('sku', 'PKG-BRIDE-01')->first();
        $this->assertCount(2, $package->rooms);
        Storage::disk('public')->assertExists($package->image_path);
    }

    public function test_vendor_cannot_add_room_from_another_showroom(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $myRoom = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $otherShowroom = Showroom::factory()->create();
        $otherRoom = Room::factory()->create(['showrooms_id' => $otherShowroom->id]);

        $response = $this->actingAs($vendor, 'web')
            ->postJson(route('vendor.packages.store'), [
                'name' => 'باكدج غير صالح',
                'description' => 'محاولة اختراق عزل المعارض',
                'price' => 50000,
                'rooms' => [
                    ['id' => $myRoom->id, 'sort_order' => 1],
                    ['id' => $otherRoom->id, 'sort_order' => 2],
                ],
            ]);

        $response->assertBadRequest()
            ->assertJsonPath('success', false);
    }

    public function test_vendor_can_update_package(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $room1 = Room::factory()->create(['showrooms_id' => $showroom->id]);
        $room2 = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $package = Package::factory()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'الاسم القديم',
            'price' => 50000,
        ]);
        $package->rooms()->attach($room1->id);

        $response = $this->actingAs($vendor, 'web')
            ->putJson(route('vendor.packages.update', ['package' => $package->id]), [
                'name' => 'الاسم الجديد المحدث',
                'price' => 55000,
                'rooms' => [
                    ['id' => $room2->id, 'sort_order' => 1],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'الاسم الجديد المحدث')
            ->assertJsonPath('data.price', 55000);

        $package->refresh();
        $this->assertEquals('الاسم الجديد المحدث', $package->name);
        $this->assertCount(1, $package->rooms);
        $this->assertEquals($room2->id, $package->rooms->first()->id);
    }

    public function test_vendor_can_delete_package(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $package = Package::factory()->create(['showrooms_id' => $showroom->id]);

        $response = $this->actingAs($vendor, 'web')
            ->deleteJson(route('vendor.packages.destroy', ['package' => $package->id]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_vendor_can_change_package_status(): void
    {
        [$vendor, $showroom] = $this->createVendorWithShowroom();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $package = Package::factory()->create([
            'showrooms_id' => $showroom->id,
            'status' => PackageStatus::DRAFT,
        ]);
        $package->rooms()->attach($room->id);

        $response = $this->actingAs($vendor, 'web')
            ->patchJson(route('vendor.packages.change-status', ['package' => $package->id]), [
                'status' => PackageStatus::PUBLISHED->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', PackageStatus::PUBLISHED->value);

        $this->assertEquals(PackageStatus::PUBLISHED, $package->fresh()->status);
        $this->assertNotNull($package->fresh()->published_at);
    }
}
