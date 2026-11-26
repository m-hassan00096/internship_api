<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListShowroomRoomsRequest;
use App\Services\V1\Admin\AdminShowroomRoomService;
use Illuminate\Http\JsonResponse;

class ShowroomRoomController extends Controller
{
    public function __construct(
        private AdminShowroomRoomService $roomService
    ) {}

    /**
     * Get rooms list, KPIs, and quota for a specific showroom.
     */
    public function index(ListShowroomRoomsRequest $request, int|string $id): JsonResponse
    {
        $data = $this->roomService->getShowroomRooms($id, $request->validated());

        return ApiResponse::success($data);
    }
}
