<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $message = 'المورد غير موجود')
    {
        parent::__construct($message, 404);
    }
}
