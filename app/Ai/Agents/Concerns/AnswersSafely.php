<?php

declare(strict_types=1);

namespace App\Ai\Agents\Concerns;

use App\Ai\ProviderFailure;
use App\Exceptions\Ai\AiProviderException;
use Throwable;

/** Sanitization sits above prompt() so FailoverableException handling is not disabled. */
trait AnswersSafely
{
    public function ask(string $prompt): string
    {
        try {
            $response = $this->prompt($prompt);
        } catch (Throwable $e) {
            throw ProviderFailure::translate($e);
        }

        $text = trim((string) $response->text);

        if ($text === '') {
            throw new AiProviderException(
                developerMessage: 'Agent '.static::class.' returned an empty response.',
            );
        }

        return $text;
    }
}
