<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\V1\Auth\AuthService;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function __invoke(LoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login(
            $data['login'],
            $data['password'],
            UserRole::VENDOR
        );

        $request->session()->regenerate();

        return ApiResponse::success(
            (new UserResource($result))->resolve($request),
            __('auth.login_success')
        );
    }
}
