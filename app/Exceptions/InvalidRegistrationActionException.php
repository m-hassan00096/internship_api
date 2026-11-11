<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class InvalidRegistrationActionException extends BusinessLogicException
{
    public function __construct(string $message)
    {
        parent::__construct(
            $message,
            HttpStatusCode::BAD_REQUEST
        );
    }
}
