<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BookingChannel;

class BookingCombo
{
    /**
     * @param  list<array{id: int, name: string, applied: float, remaining_after: float}>  $credits
     * @param  array{multiplier: float, points: float, dollars: float, line: string}|null  $earn
     * @param  list<array{id: int, name: string, decision_value: float, source: string}>  $creditPerks
     * @param  list<array{id: int, name: string, decision_value: float, source: string}>  $loyaltyPerks
     * @param  list<string>  $reasons
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $headline,
        public float $score,
        public BookingChannel $channel,
        public bool $isAward = false,
        public ?int $cardId = null,
        public ?string $cardName = null,
        public array $credits = [],
        public ?array $earn = null,
        public array $creditPerks = [],
        public array $loyaltyPerks = [],
        public array $reasons = [],
        public ?int $membershipId = null,
        public ?int $transferRouteId = null,
        public array $meta = [],
    ) {}

    public function diversityKey(): string
    {
        $creditIds = collect($this->credits)->pluck('id')->sort()->implode(',');

        return implode('|', [
            $this->isAward ? 'award' : 'cash',
            $this->channel->value,
            (string) ($this->cardId ?? 0),
            $creditIds,
            (string) ($this->transferRouteId ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'headline' => $this->headline,
            'score' => round($this->score, 2),
            'dollar_value' => round($this->score, 2),
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->description,
            'is_award' => $this->isAward,
            'card_id' => $this->cardId,
            'card_name' => $this->cardName,
            'credits' => $this->credits,
            'earn' => $this->earn,
            'credit_perks' => $this->creditPerks,
            'loyalty_perks' => $this->loyaltyPerks,
            'reasons' => $this->reasons,
            'membership_id' => $this->membershipId,
            'transfer_route_id' => $this->transferRouteId,
            'meta' => $this->meta,
        ];
    }
}
