<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\AnswersSafely;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/** Deliberately does NOT implement Conversational. */
final class BookingExplainerAgent implements Agent
{
    use AnswersSafely;
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You explain an already-ranked list of travel booking combos.
Do not add options. Do not change the order. Do not invent cards, credits, perks, or bonuses.
Speak in concise prose using only the combo JSON and wallet snapshot provided. Each combo already includes its dollar score, channel, card, credits, earn, and perks.
PROMPT;
    }

    public function provider(): Lab
    {
        return Lab::from((string) config('travel-wallet.agents.provider', 'gemini'));
    }

    public function model(): ?string
    {
        $model = config('travel-wallet.agents.model');

        return filled($model) ? (string) $model : null;
    }

    public function timeout(): int
    {
        return (int) config('travel-wallet.agents.timeout', 60);
    }
}
