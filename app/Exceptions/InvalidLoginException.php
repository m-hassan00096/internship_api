<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class InvalidLoginException extends BusinessLogicException
{
    public function __construct()
    {
        parent::__construct(
            __('auth.failed'),
            HttpStatusCode::UNAUTHORIZED
        );
    }
}
