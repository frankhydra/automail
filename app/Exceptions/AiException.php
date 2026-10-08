<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Something went wrong asking the AI. The message is written for the person using
 * AutoMail (never a raw provider response), and $status is the HTTP code to answer with.
 */
class AiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 502)
    {
        parent::__construct($message);
    }
}
