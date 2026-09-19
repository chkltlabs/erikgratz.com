<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

interface FrontierChatParser
{
    public function supports(string $payload, ?string $filename = null): bool;

    public function parse(string $payload, ?string $filename = null): ParsedFrontierChat;
}
