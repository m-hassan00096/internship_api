<?php

namespace Database\Seeders;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Showroom;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $showrooms = Showroom::with('rooms')->get();

        foreach ($showrooms as $showroom) {
            $rooms = $showroom->rooms;

            if ($rooms->isNotEmpty()) {
                $package = Package::updateOrCreate(
                    [
                        'showrooms_id' => $showroom->id,
                        'name' => 'باكدج شقة العروسة سوبر لوكس',
                    ],
                    [
                        'sku' => 'PKG-'.str_pad((string) $showroom->id, 3, '0', STR_PAD_LEFT).'-01',
                        'description' => 'عرض خاص ومميز يجمع أفضل غرف المعرض بتصميم متناسق وسعر إجمالي مخفض يشمل التوصيل والتركيب والضمان.',
                        'image_path' => null,
                        'price' => 58000.00,
                        'status' => PackageStatus::PUBLISHED,
                        'current_step' => 1,
                        'published_at' => now(),
                    ]
                );

                $attach = [];
                foreach ($rooms as $index => $room) {
                    $attach[$room->id] = ['sort_order' => $index + 1];
                }

                $package->rooms()->sync($attach);
            }
        }
    }
}
