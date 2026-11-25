<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class ShowroomVerificationException extends BusinessLogicException
{
    public function __construct(
        string $message = '',
        HttpStatusCode $status = HttpStatusCode::BAD_REQUEST
    ) {
        parent::__construct(
            message: $message,
            status: $status
        );
    }
}
