<?php

namespace App\Services\V1\Vendor;

use App\Enums\PromotionStatus;
use App\Enums\RoomStatus;
use App\Exceptions\PromotionActionException;
use App\Models\Promotion;
use App\Models\PromotionType;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PromotionService
{
    public function __construct(
        private ShowroomService $showroomService
    ) {}

    /**
     * جلب أنواع وأماكن الترويج النشطة
     *
     * @return Collection<int, PromotionType>
     */
    public function listPromotionTypes(): Collection
    {
        return PromotionType::query()
            ->active()
            ->orderBy('id')
            ->get();
    }

    /**
     * جلب قائمة إعلانات وترويجات المعرض مع الفلترة والترقيم
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPromotions(User $user, array $filters = []): LengthAwarePaginator
    {
        $showroom = $this->showroomService->getShowroom($user);

        $query = $showroom->promotions()
            ->with(['promotionType', 'promotable.primaryImage']);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['placement'])) {
            $query->whereHas('promotionType', function ($q) use ($filters): void {
                $q->where('placement', $filters['placement']);
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * جلب تفاصيل طلب ترويج معين تابع لمعرض المستخدم
     */
    public function getPromotion(User $user, int $promotionId): Promotion
    {
        $showroom = $this->showroomService->getShowroom($user);

        $promotion = $showroom->promotions()
            ->with(['promotionType', 'promotable.primaryImage'])
            ->find($promotionId);

        if (! $promotion) {
            throw new PromotionActionException(__('vendor/promotion.errors.not_found'));
        }

        return $promotion;
    }

    /**
     * تقديم طلب ترويج جديد وحساب التكلفة الإجمالية
     *
     * @param  array<string, mixed>  $data
     */
    public function createPromotion(User $user, array $data): Promotion
    {
        $showroom = $this->showroomService->getShowroom($user);

        $typeId = (int) ($data['promotion_type_id'] ?? $data['promotionTypeId']);
        $days = (int) $data['days'];
        $promotableType = $data['promotable_type'] ?? $data['promotableType'] ?? 'room';
        $promotableId = (int) ($data['promotable_id'] ?? $data['promotableId']);

        // التحقق من نوع الترويج وأنه نشط
        $promotionType = PromotionType::query()->active()->find($typeId);
        if (! $promotionType) {
            throw new PromotionActionException(__('vendor/promotion.errors.type_inactive'));
        }

        // التحقق من العنصر المروج وملكيته للمعرض وأنه منشور
        if ($promotableType === 'room' || $promotableType === Room::class) {
            $room = $showroom->rooms()->find($promotableId);
            if (! $room) {
                throw new PromotionActionException(__('vendor/promotion.errors.promotable_not_found'));
            }

            if ($room->status !== RoomStatus::PUBLISHED) {
                throw new PromotionActionException(__('vendor/promotion.errors.promotable_not_published'));
            }
        }

        // فحص عدم تكرار الطلب المعلق لنفس العنصر في نفس المكان
        $hasPending = $showroom->promotions()
            ->where('promotable_type', $promotableType)
            ->where('promotable_id', $promotableId)
            ->where('promotion_type_id', $typeId)
            ->where('status', PromotionStatus::PENDING->value)
            ->exists();

        if ($hasPending) {
            throw new PromotionActionException(__('vendor/promotion.errors.duplicate_pending'));
        }

        $dailyPrice = (float) $promotionType->daily_price;
        $totalAmount = round($days * $dailyPrice, 2);

        $promotion = $showroom->promotions()->create([
            'promotable_type' => $promotableType,
            'promotable_id' => $promotableId,
            'promotion_type_id' => $promotionType->id,
            'days' => $days,
            'daily_price' => $dailyPrice,
            'total_amount' => $totalAmount,
            'status' => PromotionStatus::PENDING,
            'created_by' => $user->id,
        ]);

        return $promotion->load(['promotionType', 'promotable.primaryImage']);
    }

    /**
     * إلغاء طلب ترويج قبل مراجعته
     */
    public function cancelPromotion(User $user, int $promotionId): bool
    {
        $promotion = $this->getPromotion($user, $promotionId);

        if ($promotion->status !== PromotionStatus::PENDING) {
            throw new PromotionActionException(__('vendor/promotion.errors.cannot_cancel'));
        }

        return (bool) $promotion->delete();
    }
}
