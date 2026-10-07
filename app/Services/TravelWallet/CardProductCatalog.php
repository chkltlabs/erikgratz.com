<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\CardProduct;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class CardProductCatalog
{
    public function __construct(private BenefitRefresher $refresher) {}

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $catalog = $this->catalogs();
        $options = [];

        foreach (CardProduct::getInstances() as $product) {
            $entry = $catalog[$product->value] ?? null;
            if ($entry === null) {
                continue;
            }

            $options[$product->value] = $entry['name'] ?? $product->description;
        }

        return $options;
    }

    public function apply(Card $card, CardProduct $product, bool $refresh = true): bool
    {
        $catalog = $this->catalogs()[$product->value] ?? null;
        if ($catalog === null) {
            return false;
        }

        $programIds = LoyaltyProgram::query()->pluck('id', 'code');
        $membershipIds = $this->upsertMemberships($card, $catalog['memberships'] ?? [], $programIds);
        $this->upsertBenefits($card, $catalog['benefits'] ?? [], $membershipIds);
        $this->upsertRates($card, $catalog['rates'] ?? []);
        $this->upsertOwnedPerks(['card_id' => $card->id], $catalog['card_perks'] ?? []);
        $this->forgetMigratedFlags($card, $product);

        if ($refresh) {
            $this->refreshCard($card);
        }

        return true;
    }

    /**
     * @return array<string, array{name?: string, memberships?: list<array{code: string, tier: ?string, number?: ?string}>, benefits?: list<array<string, mixed>>, rates?: list<array<string, mixed>>, card_perks?: list<array<string, mixed>>}>
     */
    private function catalogs(): array
    {
        return json_decode(File::get(database_path('data/held-card-benefits.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<array{code: string, tier: ?string, number?: ?string}>  $memberships
     * @param  Collection<string, int|string>  $programIds
     * @return array<string, int>
     */
    private function upsertMemberships(Card $card, array $memberships, Collection $programIds): array
    {
        $ids = [];

        foreach ($memberships as $membership) {
            $programId = $programIds[$membership['code']] ?? null;
            $userId = $card->user_id;
            if ($programId === null || $userId === null) {
                continue;
            }

            $values = [
                'tier' => $membership['tier'],
                'conferred_by_card_id' => $card->id,
            ];
            if (array_key_exists('number', $membership)) {
                $values['loyalty_number'] = $membership['number'];
            }

            $row = LoyaltyMembership::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'loyalty_program_id' => $programId,
                ],
                $values,
            );

            $ids[$membership['code']] = $row->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $benefits
     * @param  array<string, int>  $membershipIds
     */
    private function upsertBenefits(Card $card, array $benefits, array $membershipIds): void
    {
        foreach ($benefits as $benefit) {
            $program = $benefit['program'] ?? null;
            $perks = $benefit['perks'] ?? [];
            unset($benefit['program'], $benefit['perks']);

            $benefit['loyalty_membership_id'] = is_string($program)
                ? ($membershipIds[$program] ?? null)
                : null;

            $row = CardBenefit::query()->updateOrCreate(
                [
                    'card_id' => $card->id,
                    'benefit' => $benefit['benefit'],
                ],
                $benefit,
            );

            $this->upsertOwnedPerks(['card_benefit_id' => $row->id], $perks);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rates
     */
    private function upsertRates(Card $card, array $rates): void
    {
        foreach ($rates as $rate) {
            CardEarningRate::query()->updateOrCreate(
                [
                    'card_id' => $card->id,
                    'category' => $rate['category'],
                    'channel' => $rate['channel'],
                    'vendor' => filled($rate['vendor'] ?? null) ? $rate['vendor'] : null,
                ],
                [
                    'multiplier' => $rate['multiplier'],
                ],
            );
        }
    }

    /**
     * @param  array<string, int|null>  $owner
     * @param  list<array<string, mixed>>  $perks
     */
    private function upsertOwnedPerks(array $owner, array $perks): void
    {
        foreach ($perks as $perk) {
            BookingPerk::query()->updateOrCreate(
                [
                    ...$owner,
                    'name' => $perk['name'],
                ],
                [
                    'loyalty_program_id' => $owner['loyalty_program_id'] ?? null,
                    'loyalty_membership_id' => $owner['loyalty_membership_id'] ?? null,
                    'card_id' => $owner['card_id'] ?? null,
                    'card_benefit_id' => $owner['card_benefit_id'] ?? null,
                    ...$perk,
                ],
            );
        }
    }

    private function forgetMigratedFlags(Card $card, CardProduct $product): void
    {
        $names = $product->migratedFlagNames();
        if ($names === []) {
            return;
        }

        CardBenefit::query()
            ->where('card_id', $card->id)
            ->whereIn('benefit', $names)
            ->delete();
    }

    private function refreshCard(Card $card): void
    {
        $card->benefits()
            ->with(['card', 'usages'])
            ->get()
            ->each(fn (CardBenefit $benefit) => $this->refresher->refreshOne($benefit));
    }
}
