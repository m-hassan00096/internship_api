<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['ar' => 'غرف نوم رئيسية', 'en' => 'Master Bedrooms'],
                'slug' => 'master-bedrooms',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => ['ar' => 'غرف معيشة', 'en' => 'Living Rooms'],
                'slug' => 'living-rooms',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => ['ar' => 'غرف سفرة', 'en' => 'Dining Rooms'],
                'slug' => 'dining-rooms',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => ['ar' => 'غرف أطفال وشبابي', 'en' => 'Kids & Youth Rooms'],
                'slug' => 'kids-rooms',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => ['ar' => 'صالونات وركنات مودرن', 'en' => 'Salons & Corner Sofas'],
                'slug' => 'salons-corners',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
