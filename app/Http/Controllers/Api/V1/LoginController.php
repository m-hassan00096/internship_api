<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\User;
use App\Services\V1\Auth\AuthService;
use Illuminate\Http\Request;

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
            $data['password']
        );

        if (!$result) {
            return ApiResponse::error(
                __('auth.login_failed'),
                HttpStatusCode::UNAUTHORIZED
            );
        }
        $request->session()->regenerate();

        return ApiResponse::success(
            $result,
            null,
            HttpStatusCode::OK
        );
    }

}
