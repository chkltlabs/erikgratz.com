<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\AdminRecallAgent;
use App\Enums\AiMessageRole;
use App\Enums\AiSurface;
use App\Exceptions\Ai\UserSafeAiException;
use App\Models\AiSession;
use App\Models\AiSessionMessage;

class AdminRecallAssistant
{
    public function __construct(
        protected CorpusRetriever $retriever,
        protected AdminRecallAgent $agent,
    ) {}

    /**
     * @return array{answer: string, citations: list<string>, session_id: int}
     */
    public function ask(string $question, ?int $sessionId = null, ?int $userId = null): array
    {
        $question = trim($question);
        if ($question === '') {
            throw new UserSafeAiException('Question is required.');
        }

        $session = $this->resolveAdminSession($sessionId, $userId);

        $hits = $this->retriever->retrieveReadyFrontier($question);
        $context = $this->retriever->formatContext($hits);
        $citations = array_values(array_unique(array_map(
            static fn (array $hit): string => $hit['citation'],
            $hits,
        )));

        $answer = $this->agent->ask(
            "Retrieved excerpts:\n\n{$context}\n\nQuestion:\n{$question}",
        );

        $sequence = (int) $session->messages()->max('sequence');
        AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::User,
            'content' => $question,
            'sequence' => $sequence + 1,
        ]);
        AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::Assistant,
            'content' => $answer,
            'sequence' => $sequence + 2,
            'metadata' => ['citations' => $citations],
        ]);

        return [
            'answer' => $answer,
            'citations' => $citations,
            'session_id' => $session->id,
        ];
    }

    protected function resolveAdminSession(?int $sessionId, ?int $userId): AiSession
    {
        if ($sessionId !== null) {
            $existing = AiSession::query()
                ->where('id', $sessionId)
                ->where('surface', AiSurface::Admin)
                ->when(
                    $userId !== null,
                    fn ($q) => $q->where('user_id', $userId),
                )
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return AiSession::query()->create([
            'surface' => AiSurface::Admin,
            'user_id' => $userId,
            'metadata' => [],
        ]);
    }
}
