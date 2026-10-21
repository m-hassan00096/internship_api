<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

abstract class BusinessLogicException extends Exception
{
    public function __construct(
        string $message = 'Something went wrong.',
        protected HttpStatusCode $status = HttpStatusCode::BAD_REQUEST
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            $this->getMessage(),
            $this->status
        );
    }
}
