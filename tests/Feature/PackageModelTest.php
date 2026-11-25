<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageRoom;
use App\Models\Room;
use App\Models\Showroom;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_package_with_rooms(): void
    {
        $showroom = Showroom::factory()->create();
        $room1 = Room::factory()->create(['showrooms_id' => $showroom->id]);
        $room2 = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $package = Package::factory()->published()->create([
            'showrooms_id' => $showroom->id,
            'name' => 'باكدج شقة كاملة مودرن',
            'price' => 85000.00,
        ]);

        $package->rooms()->attach([
            $room1->id => ['sort_order' => 1],
            $room2->id => ['sort_order' => 2],
        ]);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'showrooms_id' => $showroom->id,
            'name' => 'باكدج شقة كاملة مودرن',
            'price' => 85000.00,
            'status' => PackageStatus::PUBLISHED->value,
        ]);

        $this->assertCount(2, $package->rooms);
        $this->assertEquals($showroom->id, $package->showroom->id);
        $this->assertTrue($package->isPublished());
        $this->assertCount(1, $room1->packages);
    }

    public function test_cannot_attach_duplicate_room_to_same_package(): void
    {
        $showroom = Showroom::factory()->create();
        $room = Room::factory()->create(['showrooms_id' => $showroom->id]);

        $package = Package::factory()->create([
            'showrooms_id' => $showroom->id,
        ]);

        PackageRoom::create([
            'packages_id' => $package->id,
            'rooms_id' => $room->id,
            'sort_order' => 1,
        ]);

        $this->expectException(QueryException::class);

        PackageRoom::create([
            'packages_id' => $package->id,
            'rooms_id' => $room->id,
            'sort_order' => 2,
        ]);
    }

    public function test_package_status_scopes(): void
    {
        Package::factory()->create(); // draft
        Package::factory()->published()->create();
        Package::factory()->archived()->create();

        $this->assertEquals(1, Package::draft()->count());
        $this->assertEquals(1, Package::published()->count());
        $this->assertEquals(1, Package::archived()->count());
    }
}
