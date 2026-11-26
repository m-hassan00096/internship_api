<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminShowroomsRequest;
use App\Http\Requests\V1\Admin\StoreAdminShowroomRequest;
use App\Http\Requests\V1\Admin\UpdateShowroomStatusRequest;
use App\Http\Resources\V1\Admin\AdminShowroomCreatedResource;
use App\Http\Resources\V1\Admin\AdminShowroomDetailResource;
use App\Http\Resources\V1\Admin\AdminShowroomListResource;
use App\Services\V1\Admin\AdminShowroomService;
use Illuminate\Http\JsonResponse;

class ShowroomController extends Controller
{
    public function __construct(
        private AdminShowroomService $service
    ) {}

    public function index(ListAdminShowroomsRequest $request): JsonResponse
    {
        $perPage = $request->integer('perPage', $request->integer('per_page', 15));
        $result = $this->service->getShowrooms($request->validated(), $perPage);

        return ApiResponse::success([
            'items' => AdminShowroomListResource::collection($result['items']->getCollection()),
            'kpis' => $result['kpis'],
            'pagination' => [
                'currentPage' => $result['items']->currentPage(),
                'lastPage' => $result['items']->lastPage(),
                'perPage' => $result['items']->perPage(),
                'total' => $result['items']->total(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $showroom = $this->service->getShowroomDetails($id);

        return ApiResponse::success(
            new AdminShowroomDetailResource($showroom)
        );
    }

    public function store(StoreAdminShowroomRequest $request): JsonResponse
    {
        $showroom = $this->service->createShowroom(
            data: $request->validated(),
            logo: $request->file('logo'),
            coverImage: $request->file('coverImage', $request->file('cover_image')),
            contractFile: $request->file('contract.contractFile', $request->file('contract.contract_file')),
            adminUser: $request->user()
        );

        return ApiResponse::success(
            new AdminShowroomCreatedResource($showroom),
            __('admin/showroom.messages.created_success'),
            HttpStatusCode::CREATED
        );
    }

    public function updateStatus(UpdateShowroomStatusRequest $request, int|string $id): JsonResponse
    {
        $this->service->updateStatus($id, $request->integer('status'));

        return ApiResponse::success(
            null,
            __('admin/showroom.messages.status_updated')
        );
    }
}
