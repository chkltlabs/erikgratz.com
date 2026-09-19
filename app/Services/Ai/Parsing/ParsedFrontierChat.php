<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

class ParsedFrontierChat
{
    /**
     * @param  list<array{role: string, content: string, occurred_at: ?string}>  $messages
     */
    public function __construct(
        public readonly string $title,
        public readonly string $source,
        public readonly ?string $model,
        public readonly ?string $externalId,
        public readonly array $messages,
        public readonly string $rawPayload,
    ) {}
}
