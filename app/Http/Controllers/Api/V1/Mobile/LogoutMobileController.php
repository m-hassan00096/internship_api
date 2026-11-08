<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\V1\Auth\MobileService;
use Illuminate\Http\Request;

class LogoutMobileController extends Controller
{
    public function __construct(
        private MobileService $mobileService
    ) {}

    public function __invoke(Request $request)
    {
        $this->mobileService->logout($request->user());

        return ApiResponse::success(
            null,
            null,
            HttpStatusCode::OK
        );
    }
}
