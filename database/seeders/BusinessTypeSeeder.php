<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            1 => [
                'ar' => 'معرض أثاث منزلي ومفروشات',
                'en' => 'Home Furniture & Furnishings Showroom',
            ],
            2 => [
                'ar' => 'مصنع أثاث وغرف نوم',
                'en' => 'Furniture & Bedroom Manufacturer',
            ],
            3 => [
                'ar' => 'أثاث مكتبي وتجاري',
                'en' => 'Office & Commercial Furniture',
            ],
            4 => [
                'ar' => 'ديكور وإكسسوارات منزلية',
                'en' => 'Home Decor & Accessories',
            ],
            5 => [
                'ar' => 'مطابخ وخزائن مدمجة',
                'en' => 'Kitchens & Built-in Wardrobes',
            ],
            6 => [
                'ar' => 'إضاءة وثريات',
                'en' => 'Lighting & Chandeliers',
            ],
            7 => [
                'ar' => 'سجاد وستائر ومفروشات',
                'en' => 'Carpets, Curtains & Textiles',
            ],
        ];

        foreach ($types as $id => $name) {
            BusinessType::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }
    }
}
