<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Enums\UserScope;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\ShowroomVerification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShowroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessType = BusinessType::first();

        // Get all vendor owners (excluding staff who have manager_id)
        $vendors = User::where('role', UserScope::VENDOR)
            ->whereNull('manager_id')
            ->get();

        $admin = User::where('role', UserScope::ADMIN)->first();

        foreach ($vendors as $index => $vendor) {
            $showroomName = $vendor->email === 'vendor1@example.com'
                ? 'معرض البيت الراقي للأثاث'
                : ($vendor->email === 'vendor2@example.com'
                    ? 'معرض مودرن هوم للديكور'
                    : 'معرض فيندر '.($index + 1));

            $showroom = Showroom::updateOrCreate(
                ['user_id' => $vendor->id],
                [
                    'business_type_id' => $businessType?->id ?? 1,
                    'name' => $showroomName,
                    'slug' => Str::slug('showroom-'.$vendor->id.'-'.Str::random(5)),
                    'bio' => 'أفضل تصاميم الأثاث المنزلي والديكور العصري بخامات عالية الجودة.',
                    'logo' => 'showrooms/logos/sample.png',
                    'cover_image' => 'showrooms/covers/sample.png',
                    'phone' => $vendor->phone ?? '01012345678',
                    'whatsapp' => '01012345678',
                    'status' => ShowroomStatus::ACTIVE,
                ]
            );

            // Active Contract for each showroom
            ShowroomContract::updateOrCreate(
                [
                    'showrooms_id' => $showroom->id,
                    'status' => ContractStatus::ACTIVE,
                ],
                [
                    'contract_number' => 'CTR-'.date('Y').'-'.str_pad((string) $showroom->id, 4, '0', STR_PAD_LEFT),
                    'started_at' => now()->startOfYear(),
                    'end_at' => now()->addYear(),
                    'amount' => 15000.00,
                    'max_rooms' => 50,
                    'max_packages' => 100,
                    'contract_file' => 'contracts/samples/contract-'.$showroom->id.'.pdf',
                    'created_by' => $admin?->id,
                ]
            );

            // Give vendor1 an active approved verification (blue badge verified)
            if ($vendor->email === 'vendor1@example.com') {
                ShowroomVerification::updateOrCreate(
                    [
                        'showrooms_id' => $showroom->id,
                        'status' => VerificationStatus::APPROVED,
                    ],
                    [
                        'month' => 6,
                        'monthly_price' => 50.00,
                        'total_amount' => 300.00,
                        'start_at' => now()->subDays(10),
                        'end_at' => now()->addDays(170),
                        'review_by' => $admin?->id,
                        'review_at' => now()->subDays(10),
                    ]
                );
            }

            // Main Branch
            $showroom->branches()->updateOrCreate(
                ['name' => 'الفرع الرئيسي - مدينة نصر'],
                [
                    'city' => 'القاهرة',
                    'address' => 'شارع مكرم عبيد، تقاطع طريق النصر، عمارة 15',
                    'phone' => $vendor->phone ?? '01012345678',
                    'whatsapp' => '01012345678',
                    'is_main' => true,
                    'is_active' => true,
                ]
            );

            // Second Branch
            $showroom->branches()->updateOrCreate(
                ['name' => 'فرع التجمع الخامس'],
                [
                    'city' => 'القاهرة',
                    'address' => 'شارع التسعين الشمالي، بجوار مجمع البنوك، مول المروة',
                    'phone' => '01123456789',
                    'whatsapp' => '01123456789',
                    'is_main' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
