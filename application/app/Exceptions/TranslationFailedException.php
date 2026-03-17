<?php

namespace App\Exceptions;

use RuntimeException;

class TranslationFailedException extends RuntimeException
{
    public function __construct(string $message = 'Translation failed', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
