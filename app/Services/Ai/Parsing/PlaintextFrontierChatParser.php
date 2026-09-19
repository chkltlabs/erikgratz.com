<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

use App\Enums\AiChatSource;
use App\Enums\AiMessageRole;

class PlaintextFrontierChatParser implements FrontierChatParser
{
    public function supports(string $payload, ?string $filename = null): bool
    {
        return trim($payload) !== '';
    }

    public function parse(string $payload, ?string $filename = null): ParsedFrontierChat
    {
        $lines = preg_split("/\r\n|\n|\r/", $payload) ?: [];
        $messages = [];
        $currentRole = AiMessageRole::User;
        $buffer = [];

        $flush = function () use (&$messages, &$buffer, &$currentRole): void {
            $content = trim(implode("\n", $buffer));
            $buffer = [];
            if ($content === '') {
                return;
            }
            $messages[] = [
                'role' => $currentRole,
                'content' => $content,
                'occurred_at' => null,
            ];
        };

        foreach ($lines as $line) {
            if (preg_match('/^(user|human|me)\s*:\s*(.*)$/i', $line, $m)) {
                $flush();
                $currentRole = AiMessageRole::User;
                $buffer[] = $m[2];

                continue;
            }
            if (preg_match('/^(assistant|model|gemini|ai|bot)\s*:\s*(.*)$/i', $line, $m)) {
                $flush();
                $currentRole = AiMessageRole::Assistant;
                $buffer[] = $m[2];

                continue;
            }
            if (preg_match('/^(system)\s*:\s*(.*)$/i', $line, $m)) {
                $flush();
                $currentRole = AiMessageRole::System;
                $buffer[] = $m[2];

                continue;
            }
            $buffer[] = $line;
        }
        $flush();

        if ($messages === []) {
            $messages[] = [
                'role' => AiMessageRole::User,
                'content' => trim($payload),
                'occurred_at' => null,
            ];
        }

        $title = $filename
            ? pathinfo($filename, PATHINFO_FILENAME)
            : 'Imported chat '.now()->format('Y-m-d H:i');

        return new ParsedFrontierChat(
            title: $title,
            source: AiChatSource::Other,
            model: null,
            externalId: null,
            messages: $messages,
            rawPayload: $payload,
        );
    }
}
