<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\V1\Auth\AuthService;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function __invoke(Request $request)
    {
        $this->authService->logout(UserRole::ADMIN);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(
            null,
            null,
        );
    }
}
