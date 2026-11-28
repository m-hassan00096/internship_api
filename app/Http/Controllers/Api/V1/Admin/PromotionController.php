<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PromotionStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminPromotionsRequest;
use App\Http\Requests\V1\Admin\UpdatePromotionPricingRequest;
use App\Http\Requests\V1\Admin\UpdatePromotionStatusRequest;
use App\Http\Resources\V1\Admin\AdminPromotionDetailResource;
use App\Services\V1\Admin\AdminPromotionService;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    public function __construct(
        private AdminPromotionService $promotionService
    ) {}

    /**
     * Screen 1: Active Promotions (الترويجات الحالية).
     */
    public function activeList(ListAdminPromotionsRequest $request): JsonResponse
    {
        $data = $this->promotionService->getActivePromotions($request->validated());

        return ApiResponse::success($data);
    }

    /**
     * Screen 2: Promotion Requests (طلبات الترويج).
     */
    public function requestsList(ListAdminPromotionsRequest $request): JsonResponse
    {
        $data = $this->promotionService->getPromotionRequests($request->validated());

        return ApiResponse::success($data);
    }

    /**
     * Screen 3: Promotion Request Details Modal (تفاصيل طلب الترويج).
     */
    public function show(int $id): JsonResponse
    {
        $promotion = $this->promotionService->getPromotionDetail($id);

        return ApiResponse::success(new AdminPromotionDetailResource($promotion));
    }

    /**
     * Approve & start instantly, or reject with reason.
     */
    public function updateStatus(UpdatePromotionStatusRequest $request, int $id): JsonResponse
    {
        $promotion = $this->promotionService->updatePromotionStatus(
            $id,
            $request->validated(),
            $request->user()
        );

        $statusValue = (int) $request->validated('status');
        $message = match ($statusValue) {
            PromotionStatus::APPROVED->value, PromotionStatus::ACTIVE->value => __('admin/promotion.messages.approved'),
            PromotionStatus::REJECTED->value => __('admin/promotion.messages.rejected'),
            default => __('admin/promotion.messages.status_updated'),
        };

        return ApiResponse::success(
            new AdminPromotionDetailResource($promotion),
            $message
        );
    }

    /**
     * Screen 4: Pricing & Settings view (الأسعار والإعدادات).
     */
    public function pricingSettings(): JsonResponse
    {
        $data = $this->promotionService->getPricingSettings();

        return ApiResponse::success($data);
    }

    /**
     * Update daily pricing for promotion types.
     */
    public function updatePricing(UpdatePromotionPricingRequest $request): JsonResponse
    {
        $data = $this->promotionService->updatePricing(
            $request->validated('prices'),
            $request->user()
        );

        return ApiResponse::success($data, __('admin/promotion.messages.pricing_updated'));
    }
}
