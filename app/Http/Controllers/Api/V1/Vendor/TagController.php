<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\TagResource;
use App\Services\V1\Vendor\RoomService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private RoomService $roomService
    ) {}

    public function index(): JsonResponse
    {
        $tags = $this->roomService->listTags();

        return ApiResponse::success(
            TagResource::collection($tags)
        );
    }
}
