<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class RoomActionException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::BAD_REQUEST
    ) {
        parent::__construct(
            message: $message ?: __('vendor/room.errors.invalid_action'),
            status: $status
        );
    }
}
