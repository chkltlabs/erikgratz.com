<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\AnswersSafely;
use App\Services\Ai\WorkHistoryKnowledgePack;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/** Deliberately does NOT implement Conversational: prior turns cannot reach the provider. */
final class PublicFitAgent implements Agent
{
    use AnswersSafely;
    use Promptable;

    public function __construct(
        private readonly WorkHistoryKnowledgePack $pack,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->pack->systemInstructions()."\n\n".$this->pack->render();
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
