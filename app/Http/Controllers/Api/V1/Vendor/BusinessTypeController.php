<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\BusinessTypeResource;
use App\Models\BusinessType;
use Illuminate\Http\JsonResponse;

class BusinessTypeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $businessTypes = BusinessType::query()
            ->active()
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            BusinessTypeResource::collection($businessTypes)
        );
    }
}
