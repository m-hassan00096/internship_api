<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\StoreVendorRegistrationRequest;
use App\Services\V1\Vendor\VendorRegistrationService;
use Illuminate\Http\JsonResponse;

class VendorRegistrationController extends Controller
{
    public function __construct(
        private VendorRegistrationService $service
    ) {}

    public function __invoke(StoreVendorRegistrationRequest $request): JsonResponse
    {
        $this->service->submitRegistration($request->validated());

        return ApiResponse::success(
            null,
            __('vendor/registration.success_submission'),
            HttpStatusCode::CREATED
        );
    }
}
