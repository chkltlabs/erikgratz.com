<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

use App\Enums\AiChatSource;
use App\Enums\AiMessageRole;
use InvalidArgumentException;

class GeminiExportParser implements FrontierChatParser
{
    public function supports(string $payload, ?string $filename = null): bool
    {
        $trimmed = ltrim($payload);
        if (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '[')) {
            return false;
        }

        $json = json_decode($payload, true);
        if (! is_array($json)) {
            return false;
        }

        return isset($json['contents'])
            || isset($json['messages'])
            || isset($json['turns'])
            || (isset($json[0]) && is_array($json[0]) && (isset($json[0]['role']) || isset($json[0]['parts'])));
    }

    public function parse(string $payload, ?string $filename = null): ParsedFrontierChat
    {
        $json = json_decode($payload, true);
        if (! is_array($json)) {
            throw new InvalidArgumentException('Gemini export must be valid JSON.');
        }

        $title = (string) ($json['title'] ?? $json['name'] ?? ($filename ? pathinfo($filename, PATHINFO_FILENAME) : 'Gemini chat'));
        $model = isset($json['model']) ? (string) $json['model'] : null;
        $externalId = $json['id'] ?? $json['conversationId'] ?? null;
        $externalId = $externalId !== null ? (string) $externalId : null;

        $rawMessages = $json['contents']
            ?? $json['messages']
            ?? $json['turns']
            ?? (array_is_list($json) ? $json : []);

        $messages = [];
        foreach ($rawMessages as $item) {
            if (! is_array($item)) {
                continue;
            }

            $role = $this->normalizeRole((string) ($item['role'] ?? $item['author'] ?? 'user'));
            $content = $this->extractText($item);
            if ($content === '') {
                continue;
            }

            $occurredAt = $item['createTime'] ?? $item['timestamp'] ?? null;
            $messages[] = [
                'role' => $role,
                'content' => $content,
                'occurred_at' => $occurredAt !== null ? (string) $occurredAt : null,
            ];
        }

        if ($messages === []) {
            throw new InvalidArgumentException('No messages found in Gemini export.');
        }

        return new ParsedFrontierChat(
            title: $title !== '' ? $title : 'Gemini chat',
            source: AiChatSource::Gemini,
            model: $model,
            externalId: $externalId,
            messages: $messages,
            rawPayload: $payload,
        );
    }

    protected function normalizeRole(string $role): string
    {
        $role = strtolower($role);

        return match ($role) {
            'model', 'assistant', 'bot' => AiMessageRole::Assistant,
            'system' => AiMessageRole::System,
            'tool', 'function' => AiMessageRole::Tool,
            default => AiMessageRole::User,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function extractText(array $item): string
    {
        if (isset($item['content']) && is_string($item['content'])) {
            return trim($item['content']);
        }

        if (isset($item['text']) && is_string($item['text'])) {
            return trim($item['text']);
        }

        if (isset($item['parts']) && is_array($item['parts'])) {
            $parts = [];
            foreach ($item['parts'] as $part) {
                if (is_string($part)) {
                    $parts[] = $part;
                } elseif (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }

            return trim(implode("\n", $parts));
        }

        return '';
    }
}
