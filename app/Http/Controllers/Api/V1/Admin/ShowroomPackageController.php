<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ListShowroomPackagesRequest;
use App\Services\V1\Admin\AdminShowroomPackageService;
use Illuminate\Http\JsonResponse;

class ShowroomPackageController extends Controller
{
    public function __construct(
        private AdminShowroomPackageService $packageService
    ) {}

    /**
     * Get packages list, KPIs, and quota for a specific showroom.
     */
    public function index(ListShowroomPackagesRequest $request, int|string $id): JsonResponse
    {
        $data = $this->packageService->getShowroomPackages($id, $request->validated());

        return ApiResponse::success($data);
    }
}
