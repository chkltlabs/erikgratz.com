<?php

declare(strict_types=1);

namespace App\Exceptions\Ai;

use RuntimeException;
use Throwable;

/** Provider failure. getMessage() is visitor-safe; detail via developerMessage(). */
class AiProviderException extends RuntimeException
{
    public function __construct(
        string $publicMessage = 'The AI provider is temporarily unavailable. Please try again.',
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
