<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class ShowroomLimitExceededException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::FORBIDDEN
    ) {
        parent::__construct(
            message: $message ?: __('vendor/showroom.errors.room_limit_exceeded'),
            status: $status
        );
    }
}
