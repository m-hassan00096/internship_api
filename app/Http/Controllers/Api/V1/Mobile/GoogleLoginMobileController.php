<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Mobile\GoogleLoginRequest;
use App\Services\V1\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;

class GoogleLoginMobileController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService
    ) {}

    public function __invoke(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->googleAuthService->handleMobileLogin(
            $request->validated('idToken')
        );

        return ApiResponse::success(
            $result,
            __('auth.login_success'),
            HttpStatusCode::OK
        );
    }
}
