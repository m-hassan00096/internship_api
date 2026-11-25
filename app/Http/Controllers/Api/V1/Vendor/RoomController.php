<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\RoomStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\ChangeRoomStatusRequest;
use App\Http\Requests\V1\Vendor\StoreRoomComponentRequest;
use App\Http\Requests\V1\Vendor\StoreRoomRequest;
use App\Http\Requests\V1\Vendor\UpdateRoomRequest;
use App\Http\Requests\V1\Vendor\UploadRoomImagesRequest;
use App\Http\Resources\V1\Vendor\RoomComponentResource;
use App\Http\Resources\V1\Vendor\RoomImageResource;
use App\Http\Resources\V1\Vendor\RoomResource;
use App\Services\V1\Vendor\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $service
    ) {}

    /**
     * عرض قائمة الغرف لمعرض التاجر مع الفلترة والترقيم
     */
    public function index(Request $request): JsonResponse
    {
        $rooms = $this->service->listRooms($request->user(), $request->all());

        return ApiResponse::success([
            'items' => RoomResource::collection($rooms->getCollection()),
            'pagination' => [
                'currentPage' => $rooms->currentPage(),
                'lastPage' => $rooms->lastPage(),
                'perPage' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    /**
     * إنشاء غرفة جديدة (مسودة)
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->service->createRoom(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            new RoomResource($room),
            __('vendor/room.messages.created'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * عرض تفاصيل غرفة معينة بكل محتوياتها
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $room = $this->service->getRoom($request->user(), $id);

        return ApiResponse::success(
            new RoomResource($room)
        );
    }

    /**
     * تحديث بيانات الغرفة
     */
    public function update(UpdateRoomRequest $request, int $id): JsonResponse
    {
        $room = $this->service->updateRoom(
            $request->user(),
            $id,
            $request->validated()
        );

        return ApiResponse::success(
            new RoomResource($room),
            __('vendor/room.messages.updated')
        );
    }

    /**
     * حذف الغرفة ومحتوياتها
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->deleteRoom($request->user(), $id);

        return ApiResponse::success(
            null,
            __('vendor/room.messages.deleted')
        );
    }

    /**
     * تغيير حالة الغرفة (مسودة / نشر / أرشفة)
     */
    public function changeStatus(ChangeRoomStatusRequest $request, int $id): JsonResponse
    {
        $status = RoomStatus::from((int) $request->validated('status'));
        $room = $this->service->changeStatus($request->user(), $id, $status);

        return ApiResponse::success(
            new RoomResource($room),
            __('vendor/room.messages.updated')
        );
    }

    /**
     * رفع صور للغرفة
     */
    public function uploadImages(UploadRoomImagesRequest $request, int $id): JsonResponse
    {
        $images = $this->service->uploadImages(
            $request->user(),
            $id,
            $request->allFiles()['images'] ?? $request->validated('images'),
            (bool) $request->validated('is_primary', false)
        );

        return ApiResponse::success(
            RoomImageResource::collection($images),
            __('vendor/room.messages.image_uploaded'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * حذف صورة من الغرفة
     */
    public function deleteImage(Request $request, int $roomId, int $imageId): JsonResponse
    {
        $this->service->deleteImage($request->user(), $roomId, $imageId);

        return ApiResponse::success(
            null,
            __('vendor/room.messages.image_deleted')
        );
    }

    /**
     * تعيين صورة رئيسية للغرفة
     */
    public function setPrimaryImage(Request $request, int $roomId, int $imageId): JsonResponse
    {
        $image = $this->service->setPrimaryImage($request->user(), $roomId, $imageId);

        return ApiResponse::success(
            new RoomImageResource($image),
            __('vendor/room.messages.primary_image_set')
        );
    }

    /**
     * إضافة مكون للغرفة
     */
    public function storeComponent(StoreRoomComponentRequest $request, int $roomId): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $component = $this->service->storeComponent(
            $request->user(),
            $roomId,
            $data
        );

        return ApiResponse::success(
            new RoomComponentResource($component),
            __('vendor/room.messages.component_added'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * تحديث مكون في الغرفة
     */
    public function updateComponent(StoreRoomComponentRequest $request, int $roomId, int $componentId): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $component = $this->service->updateComponent(
            $request->user(),
            $roomId,
            $componentId,
            $data
        );

        return ApiResponse::success(
            new RoomComponentResource($component),
            __('vendor/room.messages.component_updated')
        );
    }

    /**
     * حذف مكون من الغرفة
     */
    public function destroyComponent(Request $request, int $roomId, int $componentId): JsonResponse
    {
        $this->service->deleteComponent($request->user(), $roomId, $componentId);

        return ApiResponse::success(
            null,
            __('vendor/room.messages.component_deleted')
        );
    }
}
