<?php

namespace App\Exceptions;

use Exception;

class SlotUnavailableException extends Exception
{
    public function __construct(string $message = 'This time slot is no longer available.', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
