<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Models\User;
use App\Services\V1\Auth\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login(
            $data['login'],
            $data['password']
        );

        if (!$result) {
            return ApiResponse::error(
                'Invalid credentials.',
                HttpStatusCode::UNAUTHORIZED
            );
        }

        return ApiResponse::success(
            $result,
            'Login successful.',
            HttpStatusCode::OK
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user instanceof User) {
            return ApiResponse::error(
                'Unauthenticated.',
                HttpStatusCode::UNAUTHORIZED
            );
        }

        $this->authService->logout($user);

        return ApiResponse::success(
            null,
            'Logout successful.',
            HttpStatusCode::OK
        );
    }
}
