<?php

namespace Database\Seeders;

use App\Enums\PromotionStatus;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or update showrooms to match Figma screen names
        $firstVendor = User::where('role', 'vendor')->first() ?? User::first();
        $businessType = BusinessType::first();

        $showroomNames = [
            'Track Furniture',
            'كلاسيك هوم',
            'مفروشات المطلق',
            'أركان للمفروشات',
        ];

        $showrooms = [];
        $existingShowrooms = Showroom::all();

        foreach ($showroomNames as $index => $name) {
            if ($existingShowrooms->has($index)) {
                $sr = $existingShowrooms->get($index);
                $sr->name = $name;
                $sr->save();
                $showrooms[$name] = $sr;
            } else {
                $showrooms[$name] = Showroom::create([
                    'user_id' => $firstVendor->id,
                    'business_type_id' => $businessType?->id ?? 1,
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.Str::random(4)),
                    'status' => 1,
                ]);
            }
        }

        $track = $showrooms['Track Furniture'];
        $classic = $showrooms['كلاسيك هوم'];
        $motlaq = $showrooms['مفروشات المطلق'];
        $arkan = $showrooms['أركان للمفروشات'];

        // 2. Promotion Types (matching Figma screenshot dates and prices)
        $pricingDate = Carbon::parse('2026-10-01 10:00:00');

        $roomType = PromotionType::updateOrCreate(
            ['placement' => 'room'],
            ['name' => ['ar' => 'غرفة', 'en' => 'Room'], 'daily_price' => 200, 'is_active' => true, 'updated_at' => $pricingDate]
        );

        $packageType = PromotionType::updateOrCreate(
            ['placement' => 'package'],
            ['name' => ['ar' => 'باقة', 'en' => 'Package'], 'daily_price' => 250, 'is_active' => true, 'updated_at' => $pricingDate]
        );

        $bannerType = PromotionType::updateOrCreate(
            ['placement' => 'banner'],
            ['name' => ['ar' => 'Banner', 'en' => 'Banner'], 'daily_price' => 500, 'is_active' => true, 'updated_at' => $pricingDate]
        );

        $admin = User::first();
        $categoryId = Category::first()?->id ?? 1;

        // 3. Create Sample Rooms & Packages
        $room1 = Room::firstOrCreate(
            ['name' => 'غرفة نوم مودرن Master Suite'],
            ['showrooms_id' => $track->id, 'category_id' => $categoryId, 'sku' => 'RM-0281', 'price' => 45000, 'status' => 1]
        );

        $room2 = Room::firstOrCreate(
            ['name' => 'طقم سفرة ألترا مودرن 8 مقاعد'],
            ['showrooms_id' => $arkan->id, 'category_id' => $categoryId, 'sku' => 'RM-0282', 'price' => 38000, 'status' => 1]
        );

        $room3 = Room::firstOrCreate(
            ['name' => 'تجهيزات مطابخ ألمانية حديثة'],
            ['showrooms_id' => $track->id, 'category_id' => $categoryId, 'sku' => 'RM-0283', 'price' => 62000, 'status' => 1]
        );

        $room4 = Room::firstOrCreate(
            ['name' => 'غرفة أطفال بتصميم إسكندنافي'],
            ['showrooms_id' => $classic->id, 'category_id' => $categoryId, 'sku' => 'RM-0284', 'price' => 28000, 'status' => 1]
        );

        $room5 = Room::firstOrCreate(
            ['name' => 'مكتب إداري من خشب البلوط الطبيعي'],
            ['showrooms_id' => $motlaq->id, 'category_id' => $categoryId, 'sku' => 'RM-0285', 'price' => 22000, 'status' => 1]
        );

        $pkg1 = Package::firstOrCreate(
            ['name' => 'باقة صالون كلاسيك مذهب'],
            ['showrooms_id' => $motlaq->id, 'sku' => 'PKG-CLS-01', 'price' => 75000, 'description' => 'باقة صالون كلاسيك مذهب كاملة بأجود خامات الخشب والتقفيل الراقي.', 'status' => 1]
        );

        $pkg2 = Package::firstOrCreate(
            ['name' => 'باقة صالون كلاسيك ستة'],
            ['showrooms_id' => $motlaq->id, 'sku' => 'PKG-CLS-02', 'price' => 68000, 'description' => 'باقة صالون كلاسيك فاخرة بتصميم فندقي عصري وضمان شامل.', 'status' => 1]
        );

        // 4. Seed 8 Active Promotions (Matching Figma Tab 1)
        $activePromos = [
            [
                'showroom' => $track,
                'type' => $roomType,
                'target' => $room1,
                'target_type' => 'room',
                'days' => 14,
                'daily_price' => 200,
                'starts_at' => now()->subDays(7),
                'ends_at' => now()->addDays(7),
            ],
            [
                'showroom' => $classic,
                'type' => $bannerType,
                'target' => $classic,
                'target_type' => 'showroom',
                'days' => 6,
                'daily_price' => 500,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(5),
            ],
            [
                'showroom' => $motlaq,
                'type' => $packageType,
                'target' => $pkg1,
                'target_type' => 'package',
                'days' => 16,
                'daily_price' => 250,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(14),
            ],
            [
                'showroom' => $arkan,
                'type' => $roomType,
                'target' => $room2,
                'target_type' => 'room',
                'days' => 14,
                'daily_price' => 200,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(12),
            ],
            [
                'showroom' => $track,
                'type' => $roomType,
                'target' => $room3,
                'target_type' => 'room',
                'days' => 7,
                'daily_price' => 300,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(6),
            ],
            [
                'showroom' => $arkan,
                'type' => $bannerType,
                'target' => $arkan,
                'target_type' => 'showroom',
                'days' => 17,
                'daily_price' => 500,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(14),
            ],
            [
                'showroom' => $classic,
                'type' => $roomType,
                'target' => $room4,
                'target_type' => 'room',
                'days' => 11,
                'daily_price' => 200,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(10),
            ],
            [
                'showroom' => $motlaq,
                'type' => $roomType,
                'target' => $room5,
                'target_type' => 'room',
                'days' => 11,
                'daily_price' => 270,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(9),
            ],
        ];

        foreach ($activePromos as $item) {
            Promotion::updateOrCreate(
                [
                    'showrooms_id' => $item['showroom']->id,
                    'promotable_type' => $item['target_type'],
                    'promotable_id' => $item['target']->id,
                    'status' => PromotionStatus::ACTIVE,
                ],
                [
                    'promotion_type_id' => $item['type']->id,
                    'days' => $item['days'],
                    'daily_price' => $item['daily_price'],
                    'total_amount' => $item['days'] * $item['daily_price'],
                    'status' => PromotionStatus::ACTIVE,
                    'starts_at' => $item['starts_at'],
                    'ends_at' => $item['ends_at'],
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => $item['starts_at'],
                ]
            );
        }

        // 5. Seed 6 Pending Promotion Requests (Matching Figma Tab 2)
        $pendingPromos = [
            [
                'showroom' => $track,
                'type' => $roomType,
                'target' => $room1,
                'target_type' => 'room',
                'days' => 7,
                'daily_price' => 250,
            ],
            [
                'showroom' => $motlaq,
                'type' => $packageType,
                'target' => $pkg2,
                'target_type' => 'package',
                'days' => 10,
                'daily_price' => 250,
            ],
            [
                'showroom' => $classic,
                'type' => $bannerType,
                'target' => $classic,
                'target_type' => 'showroom',
                'days' => 10,
                'daily_price' => 500,
            ],
            [
                'showroom' => $arkan,
                'type' => $roomType,
                'target' => $room2,
                'target_type' => 'room',
                'days' => 12,
                'daily_price' => 200,
            ],
            [
                'showroom' => $track,
                'type' => $roomType,
                'target' => $room3,
                'target_type' => 'room',
                'days' => 6,
                'daily_price' => 300,
            ],
            [
                'showroom' => $arkan,
                'type' => $bannerType,
                'target' => $arkan,
                'target_type' => 'showroom',
                'days' => 14,
                'daily_price' => 500,
            ],
        ];

        // Clear existing pending test promotions to keep clean count of 6
        Promotion::where('status', PromotionStatus::PENDING)->delete();

        foreach ($pendingPromos as $item) {
            Promotion::create([
                'showrooms_id' => $item['showroom']->id,
                'promotable_type' => $item['target_type'],
                'promotable_id' => $item['target']->id,
                'promotion_type_id' => $item['type']->id,
                'days' => $item['days'],
                'daily_price' => $item['daily_price'],
                'total_amount' => $item['days'] * $item['daily_price'],
                'status' => PromotionStatus::PENDING,
                'starts_at' => null,
                'ends_at' => null,
            ]);
        }
    }
}
