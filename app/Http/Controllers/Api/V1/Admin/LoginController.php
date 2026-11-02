<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserScope;
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

        $user = $this->authService->login(
            $data['login'],
            $data['password'],
            UserScope::ADMIN
        );

        $request->session()->regenerate();

        setPermissionsTeamId(1);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'role' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 
    );
    }
}
