<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\V1\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleAuthClientController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService
    ) {}

    /**
     * Redirect client to Google OAuth or return authorization URL.
     */
    public function redirect(Request $request): JsonResponse|RedirectResponse
    {
        $targetUrl = $this->googleAuthService->getWebRedirectUrl();

        if ($request->wantsJson()) {
            return ApiResponse::success([
                'url' => $targetUrl,
            ]);
        }

        return redirect()->away($targetUrl);
    }

    /**
     * Handle Google OAuth callback for web client.
     */
    public function callback(Request $request): JsonResponse
    {
        $user = $this->googleAuthService->handleWebCallback();

        $request->session()->regenerate();

        setPermissionsTeamId(1);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], __('auth.login_success'), HttpStatusCode::OK);
    }
}
