<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class RoomNotFoundException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::NOT_FOUND
    ) {
        parent::__construct(
            message: $message ?: __('vendor/room.errors.not_found'),
            status: $status
        );
    }
}
