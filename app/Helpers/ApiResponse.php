<?php

namespace App\Helpers;

use App\Enums\HttpStatusCode;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = null,
        HttpStatusCode $status = HttpStatusCode::OK
    ) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status->value);
    }

    public static function error(
        string $message = null,
        HttpStatusCode $status = HttpStatusCode::BAD_REQUEST
    ) {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status->value);
    }
}
