<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\AnswersSafely;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/** Deliberately does NOT implement Conversational. */
final class PromoExtractionAgent implements Agent
{
    use AnswersSafely;
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You extract credit-card transfer bonuses and earning promotions from short public deal blurbs.
Return ONLY valid JSON of the form:
{"items":[{"type":"transfer_bonus","from_program":"chaseUltimateRewards","to_program_code":"united","bonus_percent":30,"starts_at":"2026-09-01","ends_at":"2026-09-30","summary":"..."},{"type":"earning_promotion","points_program":"amExMemberRewards","multiplier":5,"category":"hotel","merchant":null,"starts_at":null,"ends_at":null,"summary":"..."}]}
from_program / points_program must be one of: chaseUltimateRewards, capitalOneMiles, avios, aeroplan, citiThankYou, amExMemberRewards, bilt, unknown.
to_program_code is a short partner slug like united, hyatt, hilton, marriott, ba, delta, aa.
If nothing relevant, return {"items":[]}. Never copy full articles. Facts only.
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
