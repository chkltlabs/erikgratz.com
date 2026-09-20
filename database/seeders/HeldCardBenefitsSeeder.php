<?php

namespace Database\Seeders;

use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Services\TravelWallet\BenefitRefresher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class HeldCardBenefitsSeeder extends Seeder
{
    public function run(): void
    {
        $programIds = LoyaltyProgram::query()->pluck('id', 'code');

        foreach (Card::query()->get() as $card) {
            $product = $this->productKey($card->name);
            if ($product === null) {
                continue;
            }

            $catalog = $this->catalogs()[$product];
            $membershipIds = $this->upsertMemberships($card, $catalog['memberships'], $programIds);
            $this->upsertBenefits($card, $catalog['benefits'], $membershipIds);
            $this->upsertRates($card, $catalog['rates'] ?? []);
            $this->upsertOwnedPerks(['card_id' => $card->id], $catalog['card_perks'] ?? []);
            $this->forgetMigratedFlags($card, $product);
        }

        app(BenefitRefresher::class)->refreshAll();
    }

    private function productKey(string $name): ?string
    {
        $name = strtolower($name);

        if (str_contains($name, 'sapphire reserve')) {
            return 'csr';
        }
        if (str_contains($name, 'sapphire preferred')) {
            return 'csp';
        }
        if (str_contains($name, 'c1 v x') || str_contains($name, 'venture x')) {
            return 'vx';
        }
        if (str_contains($name, 'amex plat')) {
            return 'plat';
        }
        if (str_contains($name, 'amex green')) {
            return 'green';
        }
        if (str_contains($name, 'aer lingus')) {
            return 'aerlingus';
        }
        if (str_contains($name, 'aeroplan')) {
            return 'aeroplan';
        }
        if (str_contains($name, 'chase ba') || str_contains($name, 'british airways')) {
            return 'ba';
        }

        return null;
    }

    /**
     * @return array<string, array{memberships: list<array{code: string, tier: ?string}>, benefits: list<array<string, mixed>>}>
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

    private function forgetMigratedFlags(Card $card, string $product): void
    {
        $names = match ($product) {
            'csr' => ['IHG Platinum Elite status'],
            'plat' => ['Hilton Honors Gold', 'Marriott Bonvoy Gold'],
            'ba' => ['10% off BA flights from the US'],
            'aeroplan' => ['Aeroplan 25K status', '15% off Air Canada awards', 'Free checked bags on Air Canada'],
            default => [],
        };

        if ($names === []) {
            return;
        }

        CardBenefit::query()
            ->where('card_id', $card->id)
            ->whereIn('benefit', $names)
            ->delete();
    }
}
