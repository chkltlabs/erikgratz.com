<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\AnswersSafely;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Empty constructor: WorkHistoryKnowledgePack is unreachable from this agent.
 * Deliberately does NOT implement Conversational: session audit is not model history.
 */
final class AdminRecallAgent implements Agent
{
    use AnswersSafely;
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a private recall assistant for Erik's imported frontier-model chats.
Answer using ONLY the retrieved excerpts below. Cite excerpt titles when relevant.
If the excerpts do not contain the answer, say you do not have that information in imported chats.
Never invent content. Do not use public work-history knowledge unless it appears in the excerpts.
PROMPT;
    }

    public function provider(): Lab
    {
        return Lab::from((string) config('chatbots.agents.provider', 'gemini'));
    }

    public function model(): ?string
    {
        $model = config('chatbots.agents.model');

        return filled($model) ? (string) $model : null;
    }

    public function timeout(): int
    {
        return (int) config('chatbots.agents.timeout', 60);
    }
}
