<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\RenewContractRequest;
use App\Http\Resources\V1\Admin\AdminShowroomContractHistoryResource;
use App\Http\Resources\V1\Admin\AdminShowroomContractsResource;
use App\Http\Resources\V1\Vendor\ShowroomContractResource;
use App\Services\V1\Admin\AdminShowroomContractService;
use Illuminate\Http\JsonResponse;

class ShowroomContractController extends Controller
{
    public function __construct(
        private AdminShowroomContractService $contractService
    ) {}

    /**
     * Display commercial and active contract details for a showroom.
     */
    public function show(int|string $id): JsonResponse
    {
        $showroom = $this->contractService->getContractDetails($id);

        return ApiResponse::success(
            new AdminShowroomContractsResource($showroom)
        );
    }

    /**
     * Display historical and scheduled contracts for a showroom.
     */
    public function history(int|string $id): JsonResponse
    {
        $contracts = $this->contractService->getContractHistory($id);

        return ApiResponse::success(
            AdminShowroomContractHistoryResource::collection($contracts)
        );
    }

    /**
     * Renew and schedule a new contract for a showroom.
     */
    public function renew(RenewContractRequest $request, int|string $id): JsonResponse
    {
        $contract = $this->contractService->renewContract(
            id: $id,
            data: $request->validated(),
            contractFile: $request->file('contractFile', $request->file('contract_file')),
            adminUser: $request->user()
        );

        return ApiResponse::success(
            new ShowroomContractResource($contract),
            __('admin/showroom.messages.contract_renewed'),
            HttpStatusCode::CREATED
        );
    }
}
