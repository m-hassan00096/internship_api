<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class RoomLimitExceededException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::FORBIDDEN
    ) {
        parent::__construct(
            message: $message ?: __('vendor/room.errors.limit_reached'),
            status: $status
        );
    }
}
