<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ApproveRegistrationRequest;
use App\Http\Requests\V1\Admin\ListVendorRegistrationsRequest;
use App\Http\Requests\V1\Admin\RejectRegistrationRequest;
use App\Http\Requests\V1\Admin\UpdateRegistrationStatusRequest;
use App\Http\Resources\V1\Admin\VendorRegistrationDetailResource;
use App\Http\Resources\V1\Admin\VendorRegistrationListResource;
use App\Services\V1\Admin\VendorRegistrationService;
use Illuminate\Http\JsonResponse;

class VendorRegistrationController extends Controller
{
    public function __construct(
        private VendorRegistrationService $service
    ) {}

    public function index(ListVendorRegistrationsRequest $request): JsonResponse
    {
        $status = $request->filled('status') ? $request->integer('status') : null;
        $search = $request->filled('search') ? $request->string('search')->toString() : null;
        $perPage = $request->filled('perPage') ? $request->integer('perPage') : 15;

        $registrations = $this->service->getRegistrations($status, $search, $perPage);

        return ApiResponse::success([
            'items' => VendorRegistrationListResource::collection($registrations->getCollection()),
            'pagination' => [
                'currentPage' => $registrations->currentPage(),
                'lastPage' => $registrations->lastPage(),
                'perPage' => $registrations->perPage(),
                'total' => $registrations->total(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $registration = $this->service->getDetails($id);

        return ApiResponse::success(
            new VendorRegistrationDetailResource($registration)
        );
    }

    public function changeStatus(UpdateRegistrationStatusRequest $request, int|string $id): JsonResponse
    {
        $this->service->changeStatus(
            id: $id,
            status: $request->integer('status'),
            notes: $request->input('notes'),
            adminUser: $request->user()
        );

        return ApiResponse::success(
            null,
            __('vendor/registration.admin.status_updated')
        );
    }

    public function approve(ApproveRegistrationRequest $request, int|string $id): JsonResponse
    {
        $this->service->approveAndGenerateSetupLink(
            id: $id,
            adminUser: $request->user(),
            notes: $request->input('notes')
        );

        return ApiResponse::success(
            null,
            __('vendor/registration.admin.approved_success')
        );
    }

    public function reject(RejectRegistrationRequest $request, int|string $id): JsonResponse
    {
        $this->service->rejectRegistration(
            id: $id,
            reason: $request->string('reason')->toString(),
            adminUser: $request->user()
        );

        return ApiResponse::success(
            null,
            __('vendor/registration.admin.rejected_success')
        );
    }
}
