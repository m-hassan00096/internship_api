<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class ReviewActionException extends Exception
{
    public function render(): JsonResponse
    {
        return ApiResponse::error(
            $this->getMessage(),
            HttpStatusCode::BAD_REQUEST
        );
    }
}
