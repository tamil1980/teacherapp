<?php

namespace App\Exceptions;

use Exception;

class BookingStateException extends Exception
{
    public function __construct(string $message = 'This booking cannot be changed right now.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
