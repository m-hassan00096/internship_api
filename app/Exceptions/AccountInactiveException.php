<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class AccountInactiveException extends BusinessLogicException
{
    public function __construct()
    {
        parent::__construct(
            __('auth.email_not_verified'),
            HttpStatusCode::FORBIDDEN
        );
    }
}
