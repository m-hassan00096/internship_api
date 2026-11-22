<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\PromotionTypeResource;
use App\Services\V1\Vendor\PromotionService;
use Illuminate\Http\JsonResponse;

class PromotionTypeController extends Controller
{
    public function __construct(
        private PromotionService $service
    ) {}

    public function __invoke(): JsonResponse
    {
        $types = $this->service->listPromotionTypes();

        return ApiResponse::success(
            PromotionTypeResource::collection($types)
        );
    }
}
