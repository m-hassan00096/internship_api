<?php

namespace Database\Seeders;

use App\Enums\ReviewStatus;
use App\Enums\UserActive;
use App\Enums\UserScope;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Showroom;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. تأمين وجود عملاء للتجربة
        $client1 = User::firstOrCreate(
            ['email' => 'ahmed.client@example.com'],
            [
                'phone' => '01019876541',
                'password' => 'password123',
                'role' => UserScope::CLIENT,
                'user_active' => UserActive::ACTIVE,
            ]
        );

        $client2 = User::firstOrCreate(
            ['email' => 'sara.client@example.com'],
            [
                'phone' => '01019876542',
                'password' => 'password123',
                'role' => UserScope::CLIENT,
                'user_active' => UserActive::ACTIVE,
            ]
        );

        $client3 = User::firstOrCreate(
            ['email' => 'mahmoud.client@example.com'],
            [
                'phone' => '01019876543',
                'password' => 'password123',
                'role' => UserScope::CLIENT,
                'user_active' => UserActive::ACTIVE,
            ]
        );

        $client4 = User::firstOrCreate(
            ['email' => 'nour.client@example.com'],
            [
                'phone' => '01019876544',
                'password' => 'password123',
                'role' => UserScope::CLIENT,
                'user_active' => UserActive::ACTIVE,
            ]
        );

        $admin = User::where('role', UserScope::ADMIN)->first();

        // 2. المرور على كل المعارض الموجودة لإنشاء داتا واقعية
        $showrooms = Showroom::with('rooms')->get();

        foreach ($showrooms as $showroom) {
            $vendorUser = $showroom->user;
            $firstRoom = $showroom->rooms->first();

            // أ) تقييم معتمد على الغرفة مع رد من التاجر
            if ($firstRoom) {
                $roomReview1 = Review::updateOrCreate(
                    [
                        'user_id' => $client1->id,
                        'reviewable_type' => 'room',
                        'reviewable_id' => $firstRoom->id,
                    ],
                    [
                        'showrooms_id' => $showroom->id,
                        'rating' => 5,
                        'comment' => 'غرفة نوم ممتازة جداً وخامات الخشب زان طبيعي محترم والتقفيل راقي جداً، والتوصيل كان في الميعاد المحدد.',
                        'status' => ReviewStatus::APPROVED,
                        'reviewed_by' => $admin?->id,
                        'reviewed_at' => now()->subDays(2),
                        'reject_reason' => null,
                    ]
                );

                // رد المعرض على هذا التقييم
                ReviewReply::updateOrCreate(
                    ['reviews_id' => $roomReview1->id],
                    [
                        'user_id' => $vendorUser ? $vendorUser->id : $showroom->user_id,
                        'reply' => 'أهلاً بك يا فندم، سعداء جداً بتجربتك ونسعد دائماً بخدمتكم ورضاكم هو هدفنا الأول!',
                        'status' => ReviewStatus::APPROVED,
                        'reviewed_by' => $admin?->id,
                        'reviewed_at' => now()->subDay(),
                    ]
                );

                // ب) تقييم ثاني 4 نجوم على نفس الغرفة من عميل آخر بدون رد بعد
                Review::updateOrCreate(
                    [
                        'user_id' => $client2->id,
                        'reviewable_type' => 'room',
                        'reviewable_id' => $firstRoom->id,
                    ],
                    [
                        'showrooms_id' => $showroom->id,
                        'rating' => 4,
                        'comment' => 'الجودة ممتازة مقارنة بالسعر والشكل جميل في الواقع، لكن التوصيل اتأخر يومين عن الموعد.',
                        'status' => ReviewStatus::APPROVED,
                        'reviewed_by' => $admin?->id,
                        'reviewed_at' => now()->subDays(3),
                        'reject_reason' => null,
                    ]
                );
            }

            // ج) تقييم 5 نجوم موجه للمعرض ككل (Direct Showroom Review) مع رد
            $showroomReview = Review::updateOrCreate(
                [
                    'user_id' => $client3->id,
                    'reviewable_type' => 'showroom',
                    'reviewable_id' => $showroom->id,
                ],
                [
                    'showrooms_id' => $showroom->id,
                    'rating' => 5,
                    'comment' => 'معرض قمة في الذوق والاحترافية، وخدمة العملاء متعاونين جداً في اختيار الألوان والفرش.',
                    'status' => ReviewStatus::APPROVED,
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => now()->subDays(5),
                    'reject_reason' => null,
                ]
            );

            ReviewReply::updateOrCreate(
                ['reviews_id' => $showroomReview->id],
                [
                    'user_id' => $vendorUser ? $vendorUser->id : $showroom->user_id,
                    'reply' => 'شكراً جزيلاً لك أستاذ محمود، شهادة نعتز بها ونتمنى دوام التعامل بإذن الله.',
                    'status' => ReviewStatus::APPROVED,
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => now()->subDays(4),
                ]
            );

            // د) تقييم قيد المراجعة (PENDING) عشان التاجر يشوف الحالات المعلقة
            Review::updateOrCreate(
                [
                    'user_id' => $client4->id,
                    'reviewable_type' => 'showroom',
                    'reviewable_id' => $showroom->id,
                ],
                [
                    'showrooms_id' => $showroom->id,
                    'rating' => 5,
                    'comment' => 'تجربة ممتازة وسأقوم بالشراء مرة أخرى بالتأكيد.',
                    'status' => ReviewStatus::PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'reject_reason' => null,
                ]
            );
        }
    }
}
