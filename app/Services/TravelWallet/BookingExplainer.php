<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Ai\Agents\BookingExplainerAgent;
use App\Models\BookingIntent;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\LoyaltyMembership;

class BookingExplainer
{
    public function __construct(
        private BookingExplainerAgent $agent,
    ) {}

    /**
     * @param  list<BookingCombo>  $ranking
     */
    public function explain(BookingRequest $request, array $ranking): string
    {
        $cards = Card::query()
            ->with('bookingPerks')
            ->get(['id', 'name', 'points_program', 'points_balance', 'points_bonus', 'points_bonus_spend', 'date_opened']);

        $snapshot = [
            'cards' => $cards
                ->map(fn (Card $card): array => [
                    'id' => $card->id,
                    'name' => $card->name,
                    'program' => $card->points_program?->value,
                    'points' => $card->points_balance,
                    'sub_open' => ! $card->has_satisfied_sub,
                    'perks' => $card->bookingPerks->map(fn (BookingPerk $perk): string => $perk->name)->all(),
                ])
                ->all(),
            'memberships' => LoyaltyMembership::query()
                ->with(['program', 'user'])
                ->get()
                ->map(fn (LoyaltyMembership $membership): array => [
                    'id' => $membership->id,
                    'member' => $membership->user?->name,
                    'program' => $membership->program?->name,
                    'tier' => $membership->tier,
                    'points' => $membership->points_balance,
                ])
                ->all(),
        ];

        $prompt = json_encode([
            'request' => [
                'category' => $request->category->value,
                'vendor' => $request->vendor,
                'destination' => $request->destination,
                'cash_price' => $request->cashPrice,
                'cabin' => $request->cabin->value,
                'loyalty_membership_id' => $request->loyaltyMembershipId,
            ],
            'combos' => array_map(fn (BookingCombo $row): array => $row->toArray(), $ranking),
            'wallet' => $snapshot,
        ], JSON_THROW_ON_ERROR);

        return $this->agent->ask($prompt);
    }

    /**
     * @param  list<BookingCombo>  $ranking
     */
    public function persist(BookingRequest $request, array $ranking, ?string $explanation = null): BookingIntent
    {
        return BookingIntent::query()->create([
            'category' => $request->category,
            'activity_id' => $request->activityId,
            'vendor' => $request->vendor,
            'destination' => $request->destination,
            'cash_price' => $request->cashPrice,
            'cabin' => $request->cabin,
            'award_quotes' => $request->awardQuotes,
            'ranking' => array_map(fn (BookingCombo $row): array => $row->toArray(), $ranking),
            'explanation' => $explanation,
        ]);
    }
}
