<?php

namespace App\Exceptions;

use App\Enums\HttpStatusCode;

class AccountInactiveException extends BusinessLogicException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            $message ?? __('auth.user_active.messages.INACTIVE'),
            HttpStatusCode::FORBIDDEN
        );
    }
}
