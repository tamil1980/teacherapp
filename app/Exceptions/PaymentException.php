<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public function __construct(string $message = 'The payment could not be verified.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
