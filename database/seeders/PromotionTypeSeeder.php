<?php

namespace Database\Seeders;

use App\Models\PromotionType;
use Illuminate\Database\Seeder;

class PromotionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => [
                    'ar' => 'بانر رئيسي متحرك',
                    'en' => 'Main Banner Carousel',
                ],
                'placement' => 'banner',
                'daily_price' => 150.00,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'الصفحة الرئيسية (قسم المعروضات المميزة)',
                    'en' => 'Home Page (Featured Section)',
                ],
                'placement' => 'home',
                'daily_price' => 100.00,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'تثبيت في أعلى نتائج البحث',
                    'en' => 'Top of Search Results',
                ],
                'placement' => 'search',
                'daily_price' => 80.00,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'أعلى صفحة التصنيف',
                    'en' => 'Top of Category Page',
                ],
                'placement' => 'category',
                'daily_price' => 60.00,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            PromotionType::updateOrCreate(
                ['placement' => $type['placement']],
                $type
            );
        }
    }
}
