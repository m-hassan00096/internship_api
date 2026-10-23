<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Enums\HttpStatusCode;
use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
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
            UserRole::CLIENT
        );

        $request->session()->regenerate();

        return ApiResponse::success(
            $result,
            __('auth.login_success'),
            HttpStatusCode::OK
        );
    }
}
