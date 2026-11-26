<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ManageShowroomVerificationRequest;
use App\Http\Resources\V1\Admin\AdminShowroomVerificationResource;
use App\Http\Resources\V1\Vendor\ShowroomVerificationResource;
use App\Services\V1\Admin\AdminShowroomVerificationService;
use Illuminate\Http\JsonResponse;

class ShowroomVerificationController extends Controller
{
    public function __construct(
        private AdminShowroomVerificationService $verificationService
    ) {}

    /**
     * Display verification history for a showroom.
     */
    public function history(int|string $id): JsonResponse
    {
        $verifications = $this->verificationService->getShowroomVerifications($id);

        return ApiResponse::success(
            AdminShowroomVerificationResource::collection($verifications)
        );
    }

    /**
     * Manage and set official verification status for a showroom.
     */
    public function manage(ManageShowroomVerificationRequest $request, int|string $id): JsonResponse
    {
        $verification = $this->verificationService->manageVerification(
            id: $id,
            data: $request->validated(),
            adminUser: $request->user()
        );

        return ApiResponse::success(
            new ShowroomVerificationResource($verification),
            __('admin/showroom.messages.verification_updated')
        );
    }
}
