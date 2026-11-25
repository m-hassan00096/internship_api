<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\ApplyVerificationRequest;
use App\Http\Resources\V1\Vendor\ShowroomBranchResource;
use App\Http\Resources\V1\Vendor\ShowroomContractResource;
use App\Http\Resources\V1\Vendor\ShowroomResource;
use App\Http\Resources\V1\Vendor\ShowroomVerificationResource;
use App\Services\V1\Vendor\ShowroomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowroomController extends Controller
{
    public function __construct(
        private ShowroomService $service
    ) {}

    /**
     * عرض الملف التعريفي لمعرض التاجر
     */
    public function show(Request $request): JsonResponse
    {
        $showroom = $this->service->getShowroom($request->user());

        return ApiResponse::success(
            new ShowroomResource($showroom)
        );
    }

    /**
     * عرض تفاصيل العقد والكوتا للمعرض
     */
    public function contract(Request $request): JsonResponse
    {
        $contract = $this->service->getContract($request->user());

        return ApiResponse::success(
            new ShowroomContractResource($contract)
        );
    }

    /**
     * عرض آخر حالة أو طلب توثيق للمعرض
     */
    public function verification(Request $request): JsonResponse
    {
        $verification = $this->service->getLatestVerification($request->user());

        return ApiResponse::success(
            $verification ? new ShowroomVerificationResource($verification) : null
        );
    }

    /**
     * تقديم طلب توثيق جديد للمعرض
     */
    public function applyVerification(ApplyVerificationRequest $request): JsonResponse
    {
        $verification = $this->service->applyVerification(
            $request->user(),
            $request->integer('month')
        );

        return ApiResponse::success(
            new ShowroomVerificationResource($verification),
            __('vendor/showroom.messages.verification_submitted'),
            HttpStatusCode::CREATED
        );
    }

    /**
     * عرض فروع المعرض
     */
    public function branches(Request $request): JsonResponse
    {
        $branches = $this->service->listBranches($request->user());

        return ApiResponse::success(
            ShowroomBranchResource::collection($branches)
        );
    }
}
