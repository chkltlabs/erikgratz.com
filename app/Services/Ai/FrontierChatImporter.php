<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiImportStatus;
use App\Models\AiImportedConversation;
use App\Models\AiImportedMessage;
use App\Services\Ai\Parsing\FrontierChatParserResolver;
use App\Services\Ai\Parsing\ParsedFrontierChat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class FrontierChatImporter
{
    public function __construct(
        protected FrontierChatParserResolver $parserResolver,
    ) {}

    public function importFromPayload(
        string $payload,
        ?int $userId = null,
        ?string $filename = null,
        ?string $preferredSource = null,
        ?string $titleOverride = null,
    ): AiImportedConversation {
        $result = $this->importAllFromPayload(
            payload: $payload,
            userId: $userId,
            filename: $filename,
            preferredSource: $preferredSource,
            titleOverride: $titleOverride,
        );

        if ($result['conversations'] === []) {
            throw new InvalidArgumentException('No conversations found to import.');
        }

        return $result['conversations'][0];
    }

    /**
     * @return array{conversations: list<AiImportedConversation>, created: int, updated: int}
     */
    public function importAllFromPayload(
        string $payload,
        ?int $userId = null,
        ?string $filename = null,
        ?string $preferredSource = null,
        ?string $titleOverride = null,
    ): array {
        $parsedList = $this->parserResolver->parseAll($payload, $filename, $preferredSource);
        if ($parsedList === []) {
            throw new InvalidArgumentException('No conversations found to import.');
        }

        $conversations = [];
        $created = 0;
        $updated = 0;
        $applyTitleOverride = count($parsedList) === 1 ? $titleOverride : null;

        foreach ($parsedList as $parsed) {
            $before = AiImportedConversation::query()
                ->where('import_key', $this->importKey($parsed))
                ->exists();

            $conversation = $this->persist($parsed, $userId, $applyTitleOverride);
            $conversations[] = $conversation;

            if ($before) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'conversations' => $conversations,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function persist(ParsedFrontierChat $parsed, ?int $userId = null, ?string $titleOverride = null): AiImportedConversation
    {
        return DB::transaction(function () use ($parsed, $userId, $titleOverride): AiImportedConversation {
            $importKey = $this->importKey($parsed);

            $conversation = AiImportedConversation::query()
                ->where('import_key', $importKey)
                ->first();

            if ($conversation) {
                $conversation->messages()->delete();
                $conversation->revision = (int) $conversation->revision + 1;
            } else {
                $conversation = new AiImportedConversation;
                $conversation->revision = 1;
            }

            $title = $titleOverride ?: $parsed->title;
            if (mb_strlen($title) > AiImportedConversation::TITLE_MAX_LENGTH) {
                $title = mb_substr($title, 0, AiImportedConversation::TITLE_MAX_LENGTH);
            }

            $conversation->fill([
                'source' => $parsed->source,
                'title' => $title,
                'model' => $parsed->model,
                'external_id' => $parsed->externalId,
                'import_key' => $importKey,
                'imported_at' => now(),
                'raw_payload' => $parsed->rawPayload,
                'status' => AiImportStatus::Pending,
                'error_message' => null,
                'user_id' => $userId,
            ]);
            $conversation->save();

            foreach ($parsed->messages as $index => $message) {
                AiImportedMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'role' => $message['role'],
                    'content' => $message['content'],
                    'sequence' => $index + 1,
                    'occurred_at' => $this->parseTimestamp($message['occurred_at'] ?? null),
                ]);
            }

            return $conversation->fresh(['messages']);
        });
    }

    protected function importKey(ParsedFrontierChat $parsed): string
    {
        if ($parsed->externalId) {
            return 'external:'.$parsed->source.':'.$parsed->externalId;
        }

        $canonical = collect($parsed->messages)
            ->map(static fn (array $m): string => ($m['role'] ?? '')."\n".($m['content'] ?? ''))
            ->implode("\n---\n");

        return 'content:'.$parsed->source.':'.hash('sha256', $canonical);
    }

    protected function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
