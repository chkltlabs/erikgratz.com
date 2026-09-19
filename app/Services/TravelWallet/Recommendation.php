<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\RecommendationAction;

class Recommendation
{
    /**
     * @param  list<string>  $reasons
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public RecommendationAction $action,
        public float $score,
        public string $headline,
        public array $reasons,
        public ?int $cardId = null,
        public ?int $benefitId = null,
        public ?int $membershipId = null,
        public ?int $transferRouteId = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action->value,
            'score' => round($this->score, 2),
            'headline' => $this->headline,
            'reasons' => $this->reasons,
            'card_id' => $this->cardId,
            'benefit_id' => $this->benefitId,
            'membership_id' => $this->membershipId,
            'transfer_route_id' => $this->transferRouteId,
            'meta' => $this->meta,
        ];
    }
}
