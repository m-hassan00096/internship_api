<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\SetupPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\V1\Vendor\VendorRegistrationService;
use Illuminate\Http\JsonResponse;

class SetupPasswordController extends Controller
{
    public function __construct(
        private VendorRegistrationService $service
    ) {}

    public function __invoke(SetupPasswordRequest $request): JsonResponse
    {
        $user = $this->service->setupPasswordAndActivate(
            $request->email,
            $request->token,
            $request->password
        );

        $request->session()->regenerate();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'تم إنشاء كلمة المرور وتفعيل حسابك بنجاح، مرحباً بك في لوحة التحكم.');
    }
}
