<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\SetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\V1\Vendor\VendorRegistrationService;
use Illuminate\Http\JsonResponse;

class SetPasswordController extends Controller
{
    public function __construct(
        private VendorRegistrationService $service
    ) {}

    public function __invoke(SetPasswordRequest $request): JsonResponse
    {
        $user = $this->service->setPasswordAndActivate(
            $request->email,
            $request->token,
            $request->password
        );

        $request->session()->regenerate();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], __('vendor/registration.setup_password_success'));
    }
}
