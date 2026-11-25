<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\StorePromotionRequest;
use App\Http\Resources\V1\Vendor\PromotionResource;
use App\Services\V1\Vendor\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        private PromotionService $service
    ) {}

    /**
     * قائمة طلبات ترويج المعرض
     */
    public function index(Request $request): JsonResponse
    {
        $promotions = $this->service->listPromotions(
            $request->user(),
            $request->query()
        );

        return ApiResponse::success([
            'items' => PromotionResource::collection($promotions->getCollection()),
            'pagination' => [
                'currentPage' => $promotions->currentPage(),
                'lastPage' => $promotions->lastPage(),
                'perPage' => $promotions->perPage(),
                'total' => $promotions->total(),
            ],
        ]);
    }

    /**
     * تفاصيل طلب ترويج معين
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $promotion = $this->service->getPromotion($request->user(), $id);

        return ApiResponse::success(
            new PromotionResource($promotion)
        );
    }

    /**
     * تقديم طلب ترويج جديد
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $promotion = $this->service->createPromotion(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            new PromotionResource($promotion),
            __('vendor/promotion.messages.created'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * إلغاء طلب ترويج معلق
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->cancelPromotion($request->user(), $id);

        return ApiResponse::success(
            null,
            __('vendor/promotion.messages.cancelled')
        );
    }
}
