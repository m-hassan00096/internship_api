<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class ShowroomActionException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::BAD_REQUEST
    ) {
        parent::__construct(
            message: $message ?: __('vendor/showroom.errors.not_found'),
            status: $status
        );
    }
}
