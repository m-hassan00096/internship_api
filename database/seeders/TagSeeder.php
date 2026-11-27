<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => ['ar' => 'مودرن', 'en' => 'Modern'],
                'slug' => 'modern',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => ['ar' => 'كلاسيك', 'en' => 'Classic'],
                'slug' => 'classic',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => ['ar' => 'نيو كلاسيك', 'en' => 'Neo Classic'],
                'slug' => 'neo-classic',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => ['ar' => 'مينيمال', 'en' => 'Minimalist'],
                'slug' => 'minimalist',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => ['ar' => 'بوهيمي', 'en' => 'Bohemian'],
                'slug' => 'bohemian',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => ['ar' => 'صناعي', 'en' => 'Industrial'],
                'slug' => 'industrial',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => ['ar' => 'إسكندنافي', 'en' => 'Scandinavian'],
                'slug' => 'scandinavian',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => ['ar' => 'ريفي', 'en' => 'Rustic'],
                'slug' => 'rustic',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => ['ar' => 'معاصر', 'en' => 'Contemporary'],
                'slug' => 'contemporary',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => ['ar' => 'تقليدي', 'en' => 'Traditional'],
                'slug' => 'traditional',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => ['ar' => 'فينتيدج', 'en' => 'Vintage'],
                'slug' => 'vintage',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => ['ar' => 'ساحلي', 'en' => 'Coastal'],
                'slug' => 'coastal',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => ['ar' => 'باروكي', 'en' => 'Baroque'],
                'slug' => 'baroque',
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => ['ar' => 'آرت ديكو', 'en' => 'Art Deco'],
                'slug' => 'art-deco',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => ['ar' => 'جاباندي', 'en' => 'Japandi'],
                'slug' => 'japandi',
                'is_active' => true,
                'sort_order' => 15,
            ],
            [
                'name' => ['ar' => 'ترانزيشنال', 'en' => 'Transitional'],
                'slug' => 'transitional',
                'is_active' => false,
                'sort_order' => 16,
            ],
        ];

        foreach ($tags as $data) {
            Tag::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
