<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminTagsRequest;
use App\Http\Requests\V1\Admin\StoreAdminTagRequest;
use App\Http\Requests\V1\Admin\UpdateAdminTagRequest;
use App\Http\Resources\V1\Admin\AdminTagResource;
use App\Services\V1\Admin\AdminTagService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private AdminTagService $tagService
    ) {}

    /**
     * Display paginated tags with KPIs and filters.
     */
    public function index(ListAdminTagsRequest $request): JsonResponse
    {
        $data = $this->tagService->getTagsList($request->validated());

        return ApiResponse::success($data);
    }

    /**
     * Store a newly created tag.
     */
    public function store(StoreAdminTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->createTag(
            $request->validated(),
            $request->file('image')
        );

        return ApiResponse::success(
            new AdminTagResource($tag),
            __('admin/cms.tags.created'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * Display the specified tag.
     */
    public function show(int|string $id): JsonResponse
    {
        $tag = $this->tagService->getTagDetail($id);

        return ApiResponse::success(new AdminTagResource($tag));
    }

    /**
     * Update the specified tag.
     */
    public function update(UpdateAdminTagRequest $request, int|string $id): JsonResponse
    {
        $tag = $this->tagService->updateTag(
            $id,
            $request->validated(),
            $request->file('image')
        );

        return ApiResponse::success(
            new AdminTagResource($tag),
            __('admin/cms.tags.updated')
        );
    }

    /**
     * Toggle active status of the specified tag.
     */
    public function toggleStatus(int|string $id): JsonResponse
    {
        $tag = $this->tagService->toggleStatus($id);

        return ApiResponse::success(
            new AdminTagResource($tag),
            __('admin/cms.tags.status_updated')
        );
    }
}
