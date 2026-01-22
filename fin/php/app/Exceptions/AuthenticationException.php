<?php

namespace App\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    public function __construct(string $message = 'غير مصرح بالوصول')
    {
        parent::__construct($message, 401);
    }
}
