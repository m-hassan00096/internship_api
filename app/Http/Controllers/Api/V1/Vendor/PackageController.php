<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Enums\PackageStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\ChangePackageStatusRequest;
use App\Http\Requests\V1\Vendor\StorePackageRequest;
use App\Http\Requests\V1\Vendor\UpdatePackageRequest;
use App\Http\Resources\V1\Vendor\PackageResource;
use App\Services\V1\Vendor\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        private PackageService $service
    ) {}

    /**
     * استعراض قائمة باقات المعرض
     */
    public function index(Request $request): JsonResponse
    {
        $packages = $this->service->listPackages(
            $request->user(),
            $request->query()
        );

        return ApiResponse::success([
            'items' => PackageResource::collection($packages->getCollection()),
            'pagination' => [
                'currentPage' => $packages->currentPage(),
                'lastPage' => $packages->lastPage(),
                'perPage' => $packages->perPage(),
                'total' => $packages->total(),
            ],
        ]);
    }

    /**
     * إنشاء باقة جديدة
     */
    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = $this->service->createPackage(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            new PackageResource($package),
            __('vendor/package.messages.created'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * تفاصيل باقة محددة
     */
    public function show(Request $request, int $package): JsonResponse
    {
        $packageModel = $this->service->getPackage($request->user(), $package);

        return ApiResponse::success(
            new PackageResource($packageModel)
        );
    }

    /**
     * تعديل بيانات باقة
     */
    public function update(UpdatePackageRequest $request, int $package): JsonResponse
    {
        $packageModel = $this->service->updatePackage(
            $request->user(),
            $package,
            $request->validated()
        );

        return ApiResponse::success(
            new PackageResource($packageModel),
            __('vendor/package.messages.updated')
        );
    }

    /**
     * حذف باقة
     */
    public function destroy(Request $request, int $package): JsonResponse
    {
        $this->service->deletePackage($request->user(), $package);

        return ApiResponse::success(
            null,
            __('vendor/package.messages.deleted')
        );
    }

    /**
     * تغيير حالة الباقة (نشر، مسودة، أرشفة)
     */
    public function changeStatus(ChangePackageStatusRequest $request, int $package): JsonResponse
    {
        $status = PackageStatus::from((int) $request->validated('status'));

        $packageModel = $this->service->changeStatus(
            $request->user(),
            $package,
            $status
        );

        return ApiResponse::success(
            new PackageResource($packageModel),
            __('vendor/package.messages.status_changed')
        );
    }
}
