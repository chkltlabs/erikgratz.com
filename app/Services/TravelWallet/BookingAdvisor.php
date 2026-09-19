<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Enums\LoyaltyKind;
use App\Enums\PointsProgram;
use App\Models\Activity;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\EarningPromotion;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BookingAdvisor
{
    public function __construct(private VendorMatcher $vendors) {}

    /**
     * @return list<BookingCombo>
     */
    public function recommend(BookingRequest $request): array
    {
        $cards = Card::query()
            ->with(['earningRates', 'bookingPerks', 'benefits.usages', 'benefits.perks'])
            ->get();
        $loyalty = $this->resolveLoyalty($request);
        $promos = EarningPromotion::query()->live()->get();
        $activity = $request->activityId ? Activity::query()->find($request->activityId) : null;
        $credits = $this->expendableCredits($request, $cards, $activity);

        $combos = [
            ...$this->cashCombos($request, $cards, $credits, $loyalty, $promos),
            ...$this->awardCombos($request, $cards, $credits, $loyalty),
        ];

        return $this->pickTop($combos);
    }

    public function resolveLoyalty(BookingRequest $request): ?LoyaltyMembership
    {
        if ($request->loyaltyMembershipId) {
            return $this->loyaltyWithRelations()->find($request->loyaltyMembershipId);
        }

        $matches = $this->matchingMemberships($request);
        if ($matches->count() !== 1) {
            return null;
        }

        return $this->loyaltyWithRelations()->find($matches->first()->id);
    }

    public function needsLoyaltySelect(BookingRequest $request): bool
    {
        return $this->matchingMemberships($request)->count() !== 1
            && $this->loyaltyOptions($request) !== [];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function loyaltyOptions(BookingRequest $request): array
    {
        $matches = $this->matchingMemberships($request);
        $memberships = $matches->isNotEmpty()
            ? $matches
            : LoyaltyMembership::query()
                ->with(['program', 'user'])
                ->whereHas('program', fn ($query) => $query->where('kind', $this->kindFor($request->category)))
                ->get();

        return $memberships
            ->map(fn (LoyaltyMembership $membership): array => [
                'id' => $membership->id,
                'label' => $membership->displayLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Card>  $cards
     * @param  Collection<int, CardBenefit>  $credits
     * @param  Collection<int, EarningPromotion>  $promos
     * @return list<BookingCombo>
     */
    private function cashCombos(
        BookingRequest $request,
        Collection $cards,
        Collection $credits,
        ?LoyaltyMembership $loyalty,
        Collection $promos,
    ): array {
        $out = [];

        foreach ($cards as $card) {
            foreach ($this->channelsFor($card) as $channel) {
                $out[] = $this->buildCashCombo($request, $card, $channel, null, $loyalty, $promos);

                foreach ($credits as $credit) {
                    if ($credit->award_only) {
                        continue;
                    }
                    if ($credit->card_id !== $card->id) {
                        continue;
                    }
                    if ($credit->required_channel !== null && ! $credit->required_channel->is($channel)) {
                        continue;
                    }
                    if ($credit->applyableAmount($request) <= 0) {
                        continue;
                    }

                    $out[] = $this->buildCashCombo($request, $card, $channel, $credit, $loyalty, $promos);
                }
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, EarningPromotion>  $promos
     */
    private function buildCashCombo(
        BookingRequest $request,
        Card $card,
        BookingChannel $channel,
        ?CardBenefit $credit,
        ?LoyaltyMembership $loyalty,
        Collection $promos,
    ): BookingCombo {
        $price = $request->cashPrice ?? 0;
        $applied = 0.0;
        $remainingAfter = 0.0;
        $credits = [];
        $reasons = [];

        if ($credit !== null) {
            $remaining = $credit->remaining();
            $applied = $credit->applyableAmount($request, $request->cashPrice);
            $remainingAfter = round(max(0, $remaining - $applied), 2);
            $credits[] = [
                'id' => $credit->id,
                'name' => $credit->benefit,
                'applied' => $applied,
                'remaining_after' => $remainingAfter,
            ];
            $reasons[] = sprintf('Apply $%s of %s ($%s left after)', number_format($applied, 0), $credit->benefit, number_format($remainingAfter, 0));
        }

        $earnable = max(0, $price - $applied);
        $multiplier = $this->multiplier($card, $request, $channel, $promos);
        $points = $earnable * $multiplier;
        $cpp = $this->centsPerPoint($card->points_program?->value);
        $earnDollars = $points * ($cpp / 100);
        $earn = [
            'multiplier' => $multiplier,
            'points' => $points,
            'dollars' => $earnDollars,
            'line' => $earnable <= 0
                ? 'No earn on the credited amount'
                : sprintf('Earn %.0f pts at %.1fx (≈ $%.0f)', $points, $multiplier, $earnDollars),
        ];
        $reasons[] = $earn['line'];

        [$creditPerks, $loyaltyPerks] = $this->splitPerks($request, $channel, false, $card, $credit, $loyalty);
        $perkValue = collect($creditPerks)->sum('decision_value') + collect($loyaltyPerks)->sum('decision_value');

        $score = $applied + $earnDollars + $perkValue;
        $score += $this->subAdditive($card, $reasons);

        $headline = $channel->description.' + '.$card->name;
        if ($credit !== null) {
            $headline .= ' + '.$credit->benefit;
        }

        return new BookingCombo(
            headline: $headline,
            score: $score,
            channel: $channel,
            cardId: $card->id,
            cardName: $card->name,
            credits: $credits,
            earn: $earn,
            creditPerks: $creditPerks,
            loyaltyPerks: $loyaltyPerks,
            reasons: $reasons,
            membershipId: $loyalty?->id,
        );
    }

    /**
     * @param  Collection<int, Card>  $cards
     * @param  Collection<int, CardBenefit>  $credits
     * @return list<BookingCombo>
     */
    private function awardCombos(BookingRequest $request, Collection $cards, Collection $credits, ?LoyaltyMembership $loyalty): array
    {
        if ($request->awardQuotes === []) {
            return [];
        }

        $awardCredits = $credits
            ->filter(fn (CardBenefit $credit): bool => $credit->award_only)
            ->values();
        $routes = TransferRoute::query()->active()->with(['program', 'bonuses'])->get();
        $awardPerks = $this->awardMechanicPerks($request, $cards, $loyalty);
        $discount = $this->awardDiscountFraction($awardPerks);
        $extraTransfer = $this->extraTransferBonusPercent($awardPerks);
        $out = [];

        foreach ($request->awardQuotes as $quote) {
            $quoteProgram = strtolower((string) ($quote['points_program'] ?? ''));
            $points = (int) ($quote['points'] ?? 0);
            $quoteCash = (float) ($quote['cash'] ?? 0);
            $code = strtolower((string) ($quote['program_code'] ?? $request->vendor ?? ''));
            $adjustedPoints = (int) round($points * (1 - $discount));

            foreach ($cards as $card) {
                if (! $this->cardHoldsCurrency($card, $quoteProgram, $code)) {
                    continue;
                }

                $out[] = $this->buildAwardCombo(
                    request: $request,
                    card: $card,
                    loyalty: $loyalty,
                    quoteCash: $quoteCash,
                    sourcePoints: $adjustedPoints,
                    originalPoints: $points,
                    discount: $discount,
                    bonus: null,
                    extraTransfer: 0,
                    route: null,
                    credits: $awardCredits,
                );
            }

            foreach ($routes as $route) {
                if (! $this->routeMatchesAward($route, $quoteProgram, $code)) {
                    continue;
                }

                $fromCards = $cards->filter(
                    fn (Card $card): bool => $card->points_program?->is($route->from_program) ?? false
                );
                if ($fromCards->isEmpty()) {
                    continue;
                }

                $bonus = $route->activeBonus();
                $bonusPercent = (float) ($bonus?->bonus_percent ?? 0);
                $appliedExtra = ($bonus !== null && $extraTransfer > 0) ? $extraTransfer : 0;
                $sourcePoints = $adjustedPoints / max(0.0001, (float) $route->base_ratio);
                if ($bonusPercent + $appliedExtra > 0) {
                    $sourcePoints = $sourcePoints / (1 + (($bonusPercent + $appliedExtra) / 100));
                }

                $card = $fromCards->sortByDesc('points_balance')->first();
                $out[] = $this->buildAwardCombo(
                    request: $request,
                    card: $card,
                    loyalty: $loyalty,
                    quoteCash: $quoteCash,
                    sourcePoints: $sourcePoints,
                    originalPoints: $points,
                    discount: $discount,
                    bonus: $bonus,
                    extraTransfer: $appliedExtra,
                    route: $route,
                    credits: $awardCredits,
                );
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, CardBenefit>  $credits
     */
    private function buildAwardCombo(
        BookingRequest $request,
        Card $card,
        ?LoyaltyMembership $loyalty,
        float $quoteCash,
        float $sourcePoints,
        int $originalPoints,
        float $discount,
        ?TransferBonus $bonus,
        float $extraTransfer,
        ?TransferRoute $route,
        Collection $credits,
    ): BookingCombo {
        $channel = $this->channelsFor($card)[0] ?? BookingChannel::Direct();
        $cpp = $this->centsPerPoint($card->points_program?->value);
        $pointsCost = $sourcePoints * ($cpp / 100);
        $appliedCredits = [];
        $reasons = [];
        $appliedTotal = 0.0;

        foreach ($credits as $credit) {
            $applied = $credit->applyableAmount($request, $quoteCash - $appliedTotal);
            if ($applied <= 0) {
                continue;
            }

            $remainingAfter = round(max(0, $credit->remaining() - $applied), 2);
            $appliedTotal += $applied;
            $appliedCredits[] = [
                'id' => $credit->id,
                'name' => $credit->benefit,
                'applied' => $applied,
                'remaining_after' => $remainingAfter,
            ];
            $reasons[] = sprintf('Apply $%s of %s ($%s left after)', number_format($applied, 0), $credit->benefit, number_format($remainingAfter, 0));
        }

        $outOfPocket = max(0, $quoteCash - $appliedTotal);
        $cashSaved = max(0, ($request->cashPrice ?? 0) - $outOfPocket);

        if ($discount > 0) {
            $reasons[] = sprintf('%s%% off award (need %s pts instead of %s)', number_format($discount * 100, 0), number_format($originalPoints * (1 - $discount), 0), number_format($originalPoints, 0));
        }

        if ($route) {
            $reasons[] = sprintf(
                'Transfer %s → %s at %s:1 (≈ %.0f source pts at %.1f¢)',
                $route->from_program->description,
                $route->program->name,
                $route->base_ratio,
                $sourcePoints,
                $cpp,
            );
            if ($bonus) {
                $reasons[] = $bonus->bonus_percent.'% transfer bonus is live';
            }
            if ($extraTransfer > 0) {
                $reasons[] = $extraTransfer.'% extra transfer bonus from card perk';
            }
        } else {
            $reasons[] = sprintf('Redeem %.0f %s at %.1f¢/pt', $sourcePoints, $card->points_program?->description ?? 'points', $cpp);
        }

        [$creditPerks, $loyaltyPerks] = $this->splitPerks($request, $channel, true, $card, null, $loyalty);
        $perkValue = collect($creditPerks)->sum('decision_value') + collect($loyaltyPerks)->sum('decision_value');
        $score = $cashSaved - $pointsCost + $perkValue;

        $headline = $route
            ? 'Transfer to '.$route->program->name.' for this award'
            : 'Book award on '.$card->name;

        return new BookingCombo(
            headline: $headline,
            score: $score,
            channel: $channel,
            isAward: true,
            cardId: $card->id,
            cardName: $card->name,
            credits: $appliedCredits,
            creditPerks: $creditPerks,
            loyaltyPerks: $loyaltyPerks,
            reasons: $reasons,
            membershipId: $loyalty?->id,
            transferRouteId: $route?->id,
            meta: [
                'source_points' => $sourcePoints,
                'points_needed' => $originalPoints * (1 - $discount),
                'cash_saved' => $cashSaved,
            ],
        );
    }

    /**
     * @param  Collection<int, Card>  $cards
     * @return Collection<int, CardBenefit>
     */
    private function expendableCredits(BookingRequest $request, Collection $cards, ?Activity $activity): Collection
    {
        return $cards
            ->flatMap(fn (Card $card) => $card->benefits)
            ->filter(function (CardBenefit $benefit) use ($request, $activity): bool {
                if (! $benefit->isExpendableCredit()) {
                    return false;
                }
                if ($benefit->remaining() <= 0) {
                    return false;
                }
                if ($benefit->remainingUses() === 0) {
                    return false;
                }
                if (! $benefit->appliesToCategory($request->category->value)) {
                    return false;
                }
                if (! $benefit->matchesVendor($request->vendor)) {
                    return false;
                }

                return $benefit->matchesLocation(
                    null,
                    $request->destination,
                    $activity?->location_name ?? $request->destination,
                );
            })
            ->values();
    }

    /**
     * @return list<BookingChannel>
     */
    private function channelsFor(Card $card): array
    {
        $channels = [BookingChannel::Direct()];
        $program = $card->points_program;

        if ($program?->in([
            PointsProgram::ChaseUltimateRewards,
            PointsProgram::Aeroplan,
            PointsProgram::Avios,
        ])) {
            $channels[] = BookingChannel::ChaseTravel();
        }

        if ($program?->is(PointsProgram::AmExMemberRewards)) {
            $channels[] = BookingChannel::AmexTravel();
        }

        if ($program?->is(PointsProgram::CapitalOneMiles)) {
            $channels[] = BookingChannel::CapitalOneTravel();
        }

        return $channels;
    }

    /**
     * @param  Collection<int, EarningPromotion>  $promos
     */
    private function multiplier(Card $card, BookingRequest $request, BookingChannel $channel, Collection $promos): float
    {
        $vendor = strtolower(trim((string) $request->vendor));
        $match = $card->earningRates
            ->filter(fn (CardEarningRate $rate): bool => $rate->category->is($request->category) && $rate->channel->is($channel))
            ->sortByDesc(fn (CardEarningRate $rate): int => filled($rate->vendor) ? 1 : 0)
            ->first(function (CardEarningRate $rate) use ($vendor): bool {
                if (! filled($rate->vendor)) {
                    return true;
                }
                if ($vendor === '') {
                    return false;
                }

                $needle = strtolower((string) $rate->vendor);

                return str_contains($vendor, $needle) || str_contains($needle, $vendor);
            });

        $rate = $match?->multiplier ?? (float) config('travel-wallet.default_earning_rate', 1);

        foreach ($promos as $promo) {
            if ($promo->card_id && $promo->card_id !== $card->id) {
                continue;
            }
            if ($promo->points_program && $card->points_program?->value !== $promo->points_program->value) {
                continue;
            }
            if ($promo->category && $promo->category !== $request->category->value) {
                continue;
            }
            $rate = max($rate, (float) $promo->multiplier);
        }

        return $rate;
    }

    /**
     * @param  list<string>  $reasons
     */
    private function subAdditive(Card $card, array &$reasons): float
    {
        if ($card->has_satisfied_sub) {
            return 0;
        }

        $days = max(0, now()->diffInDays($card->points_bonus_deadline, false));
        $amount = (float) config('travel-wallet.scoring.sub_boost', 25)
            + ($days * (float) config('travel-wallet.scoring.sub_urgency_per_day', 0.25));
        $reasons[] = 'Signup bonus still open until '.$card->points_bonus_deadline->toFormattedDateString();

        return $amount;
    }

    /**
     * @return array{0: list<array{id: int, name: string, decision_value: float, source: string}>, 1: list<array{id: int, name: string, decision_value: float, source: string}>}
     */
    private function splitPerks(
        BookingRequest $request,
        BookingChannel $channel,
        bool $isAward,
        Card $card,
        ?CardBenefit $credit,
        ?LoyaltyMembership $loyalty,
    ): array {
        $creditModels = collect();
        $loyaltyModels = collect();

        if ($credit !== null) {
            foreach ($credit->perks as $perk) {
                $creditModels->push(['perk' => $perk, 'source' => 'credit']);
            }
        }

        foreach ($card->bookingPerks as $perk) {
            $creditModels->push(['perk' => $perk, 'source' => 'card']);
        }

        if ($loyalty !== null) {
            foreach ($loyalty->program?->perks ?? [] as $perk) {
                if (! $this->membershipMeetsMinTier($loyalty->tier, $perk->min_tier)) {
                    continue;
                }
                $loyaltyModels->push(['perk' => $perk, 'source' => 'program']);
            }
            foreach ($loyalty->perks as $perk) {
                $loyaltyModels->push(['perk' => $perk, 'source' => 'membership']);
            }
            if ($loyalty->conferredByCard && $loyalty->conferred_by_card_id !== $card->id) {
                foreach ($loyalty->conferredByCard->bookingPerks as $perk) {
                    $loyaltyModels->push(['perk' => $perk, 'source' => 'conferring_card']);
                }
            }
        }

        $toPayloads = function (Collection $rows) use ($request, $channel, $isAward): array {
            return $rows
                ->unique(fn (array $row): int => $row['perk']->id)
                ->filter(fn (array $row): bool => $this->perkApplies($row['perk'], $request, $channel, $isAward))
                ->map(fn (array $row): array => $this->perkPayload($row['perk'], $row['source']))
                ->values()
                ->all();
        };

        return [$toPayloads($creditModels), $toPayloads($loyaltyModels)];
    }

    private function perkApplies(?BookingPerk $perk, BookingRequest $request, BookingChannel $channel, bool $isAward): bool
    {
        if ($perk === null) {
            return false;
        }
        if (! $perk->appliesToCategory($request->category->value)) {
            return false;
        }
        if ($perk->channel !== null && ! $perk->channel->is($channel)) {
            return false;
        }
        if ($perk->award_only && ! $isAward) {
            return false;
        }

        return true;
    }

    /**
     * @return array{id: int, name: string, decision_value: float, source: string}
     */
    private function perkPayload(BookingPerk $perk, string $source): array
    {
        return [
            'id' => $perk->id,
            'name' => $perk->name,
            'decision_value' => (float) $perk->decision_value,
            'source' => $source,
        ];
    }

    /**
     * @param  Collection<int, Card>  $cards
     * @return Collection<int, BookingPerk>
     */
    private function awardMechanicPerks(BookingRequest $request, Collection $cards, ?LoyaltyMembership $loyalty): Collection
    {
        $perks = $cards->flatMap(fn (Card $card) => $card->bookingPerks);

        if ($loyalty?->conferredByCard) {
            $perks = $perks->merge($loyalty->conferredByCard->bookingPerks);
        }

        return $perks
            ->unique('id')
            ->filter(fn (BookingPerk $perk): bool => $perk->award_only && $perk->appliesToCategory($request->category->value));
    }

    /**
     * @param  Collection<int, BookingPerk>  $perks
     */
    private function awardDiscountFraction(Collection $perks): float
    {
        foreach ($perks as $perk) {
            if (preg_match('/(\d+)\s*%\s*off/i', $perk->name, $matches) === 1) {
                return ((float) $matches[1]) / 100;
            }
        }

        return 0;
    }

    /**
     * @param  Collection<int, BookingPerk>  $perks
     */
    private function extraTransferBonusPercent(Collection $perks): float
    {
        foreach ($perks as $perk) {
            if (preg_match('/(\d+)\s*%\s*extra/i', $perk->name, $matches) === 1 && str_contains(strtolower($perk->name), 'transfer')) {
                return (float) $matches[1];
            }
        }

        return 0;
    }

    private function cardHoldsCurrency(Card $card, string $quoteProgram, string $code): bool
    {
        $value = strtolower((string) $card->points_program?->value);
        if ($value === '' || $value === 'unknown') {
            return false;
        }

        foreach ([$quoteProgram, $code] as $needle) {
            if ($needle !== '' && ($value === $needle || str_contains($value, $needle) || str_contains($needle, $value))) {
                return true;
            }
        }

        return false;
    }

    private function routeMatchesAward(TransferRoute $route, string $quoteProgram, string $code): bool
    {
        $partnerCode = strtolower($route->program->code);
        $partnerName = strtolower($route->program->name);

        foreach ([$quoteProgram, $code] as $needle) {
            if ($needle === '') {
                continue;
            }
            if ($partnerCode === $needle || str_contains($partnerName, $needle) || str_contains($needle, $partnerCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, LoyaltyMembership>
     */
    private function matchingMemberships(BookingRequest $request): Collection
    {
        $programs = $this->matchingPrograms($request);
        if ($programs->isEmpty()) {
            return collect();
        }

        return LoyaltyMembership::query()
            ->with(['program', 'user'])
            ->whereIn('loyalty_program_id', $programs->modelKeys())
            ->get();
    }

    private function loyaltyWithRelations(): Builder
    {
        return LoyaltyMembership::query()
            ->with(['program.perks', 'perks', 'user', 'conferredByCard.bookingPerks']);
    }

    /**
     * @return Collection<int, LoyaltyProgram>
     */
    private function matchingPrograms(BookingRequest $request): Collection
    {
        $vendor = strtolower(trim((string) $request->vendor));
        if ($vendor === '') {
            return collect();
        }

        $kind = $this->kindFor($request->category);

        return LoyaltyProgram::query()
            ->where('kind', $kind)
            ->get()
            ->filter(fn (LoyaltyProgram $program): bool => $this->vendors->programMatches($program, $vendor))
            ->values();
    }

    private function membershipMeetsMinTier(?string $membershipTier, ?string $minTier): bool
    {
        if (! filled($minTier)) {
            return true;
        }
        if (! filled($membershipTier)) {
            return false;
        }

        $ranks = [
            'member' => 0,
            'club' => 0,
            'silver' => 1,
            'discoverist' => 1,
            'gold' => 2,
            'platinum' => 3,
            'platinum elite' => 3,
            'diamond' => 4,
            'titanium' => 4,
            'ambassador' => 5,
            '25k' => 1,
            '35k' => 2,
            '50k' => 3,
            '75k' => 4,
            '100k' => 5,
        ];

        $have = $ranks[strtolower((string) $membershipTier)] ?? null;
        $need = $ranks[strtolower((string) $minTier)] ?? null;
        if ($have !== null && $need !== null) {
            return $have >= $need;
        }

        return str_contains(strtolower((string) $membershipTier), strtolower((string) $minTier));
    }

    private function kindFor(BookingCategory $category): string
    {
        return match ($category->value) {
            BookingCategory::Flight => LoyaltyKind::Airline,
            BookingCategory::Hotel => LoyaltyKind::Hotel,
            default => LoyaltyKind::Car,
        };
    }

    /**
     * @param  list<BookingCombo>  $combos
     * @return list<BookingCombo>
     */
    private function pickTop(array $combos, int $limit = 5): array
    {
        usort($combos, fn (BookingCombo $left, BookingCombo $right): int => $right->score <=> $left->score);

        $picked = [];
        $seen = [];

        foreach ($combos as $combo) {
            $key = $combo->diversityKey();
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $picked[] = $combo;
            if (count($picked) === $limit) {
                return $picked;
            }
        }

        return $picked;
    }

    private function centsPerPoint(?string $program): float
    {
        $map = config('travel-wallet.cents_per_point', []);

        return (float) ($map[$program] ?? 1.0);
    }
}
