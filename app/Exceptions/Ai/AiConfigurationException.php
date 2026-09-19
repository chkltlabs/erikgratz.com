<?php

declare(strict_types=1);

namespace App\Exceptions\Ai;

use RuntimeException;
use Throwable;

/** Missing or invalid AI configuration. getMessage() is visitor-safe. */
class AiConfigurationException extends RuntimeException
{
    public function __construct(
        string $publicMessage = 'The AI assistant is not configured yet.',
        private readonly string $developerMessage = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($publicMessage, 0, $previous);
    }

    public function developerMessage(): string
    {
        return $this->developerMessage;
    }
}
