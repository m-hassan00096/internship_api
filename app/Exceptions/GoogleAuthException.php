<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class GoogleAuthException extends BusinessLogicException
{
    public function __construct(
        string $message,
        HttpStatusCode $status = HttpStatusCode::UNAUTHORIZED
    ) {
        parent::__construct($message, $status);
    }
}
