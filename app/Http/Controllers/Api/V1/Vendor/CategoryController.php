<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\CategoryResource;
use App\Services\V1\Vendor\RoomService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private RoomService $roomService
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->roomService->listCategories();

        return ApiResponse::success(
            CategoryResource::collection($categories)
        );
    }
}
