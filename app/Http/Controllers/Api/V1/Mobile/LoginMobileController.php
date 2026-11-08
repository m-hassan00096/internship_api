<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Services\V1\Auth\MobileService;

class LoginMobileController extends Controller
{
    public function __construct(
        private MobileService $mobileService
    ) {}

    public function __invoke(LoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->mobileService->login(
            $data['login'],
            $data['password']
        );

        return ApiResponse::success(
            $result,
            __('auth.login_success'),
            HttpStatusCode::OK
        );
    }
}
