<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class InvalidLogoutException extends BusinessLogicException
{
    public function __construct()
    {
        parent::__construct(
            __('auth.logout_failed'),
            HttpStatusCode::UNAUTHORIZED
        );
    }
}
