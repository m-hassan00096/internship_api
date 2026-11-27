<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListAdminCategoriesRequest;
use App\Http\Requests\V1\Admin\StoreAdminCategoryRequest;
use App\Http\Requests\V1\Admin\UpdateAdminCategoryRequest;
use App\Http\Resources\V1\Admin\AdminCategoryResource;
use App\Services\V1\Admin\AdminCategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private AdminCategoryService $categoryService
    ) {}

    /**
     * Display paginated categories with KPIs and filters.
     */
    public function index(ListAdminCategoriesRequest $request): JsonResponse
    {
        $data = $this->categoryService->getCategoriesList($request->validated());

        return ApiResponse::success($data);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreAdminCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory(
            $request->validated(),
            $request->file('image')
        );

        return ApiResponse::success(
            new AdminCategoryResource($category),
            __('admin/cms.categories.created'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * Display the specified category.
     */
    public function show(int|string $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryDetail($id);

        return ApiResponse::success(new AdminCategoryResource($category));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateAdminCategoryRequest $request, int|string $id): JsonResponse
    {
        $category = $this->categoryService->updateCategory(
            $id,
            $request->validated(),
            $request->file('image')
        );

        return ApiResponse::success(
            new AdminCategoryResource($category),
            __('admin/cms.categories.updated')
        );
    }

    /**
     * Toggle active status of the specified category.
     */
    public function toggleStatus(int|string $id): JsonResponse
    {
        $category = $this->categoryService->toggleStatus($id);

        return ApiResponse::success(
            new AdminCategoryResource($category),
            __('admin/cms.categories.status_updated')
        );
    }
}
