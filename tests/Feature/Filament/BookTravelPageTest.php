<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\BenefitAppliesTo;
use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Enums\LoyaltyKind;
use App\Enums\PointsProgram;
use App\Filament\Pages\BookTravel;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookTravelPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function rank_populates_combo_shape(): void
    {
        Card::factory()->create([
            'name' => 'Sapphire',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);

        Livewire::test(BookTravel::class)
            ->fillForm([
                'category' => BookingCategory::Flight,
                'vendor' => 'United',
                'cash_price' => 400,
                'cabin' => BookingCabin::Economy,
            ])
            ->call('rank')
            ->assertSuccessful()
            ->assertSet('ranking', function ($ranking): bool {
                if (! is_array($ranking) || $ranking === []) {
                    return false;
                }

                $first = $ranking[0];

                return isset($first['headline'], $first['dollar_value'], $first['channel'], $first['card_name'])
                    && array_key_exists('credits', $first)
                    && array_key_exists('loyalty_perks', $first);
            })
            ->assertSee('Top combos')
            ->assertSee('Sapphire')
            ->assertSee('fi-in-repeatable', escape: false)
            ->assertDontSee('credit_perks_summary');
    }

    #[Test]
    public function changing_loyalty_select_updates_ranking_perks(): void
    {
        $card = Card::factory()->create([
            'name' => 'CSR',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'name' => 'World of Hyatt',
            'code' => 'hyatt',
        ]);
        BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_program_id' => $program->id,
            'min_tier' => 'Discoverist',
            'name' => 'Hyatt late checkout',
            'decision_value' => 20,
            'applies_to' => BenefitAppliesTo::Hotel,
        ]);
        $membership = LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $program->id,
            'conferred_by_card_id' => $card->id,
            'tier' => 'Discoverist',
        ]);

        Livewire::test(BookTravel::class)
            ->fillForm([
                'category' => BookingCategory::Hotel,
                'vendor' => null,
                'cash_price' => 300,
            ])
            ->call('rank')
            ->assertSet('showLoyaltySelect', true)
            ->assertSet('ranking', fn ($ranking): bool => is_array($ranking)
                && $ranking !== []
                && collect($ranking)->every(fn (array $row): bool => ($row['loyalty_perks'] ?? []) === []))
            ->set('selectedLoyaltyMembershipId', $membership->id)
            ->assertSet('ranking', fn ($ranking): bool => is_array($ranking)
                && collect($ranking)->contains(
                    fn (array $row): bool => collect($row['loyalty_perks'] ?? [])->contains('name', 'Hyatt late checkout')
                ))
            ->assertSee('Hyatt late checkout')
            ->assertSee('Loyalty perks');
    }

    #[Test]
    public function two_aeroplan_accounts_show_member_names_in_the_loyalty_select(): void
    {
        Card::factory()->create([
            'name' => 'Sapphire',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        $amy = User::factory()->create(['name' => 'Amy']);
        $erik = User::factory()->create(['name' => 'Erik']);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Air Canada Aeroplan',
            'code' => 'aeroplan',
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $amy->id,
            'loyalty_program_id' => $program->id,
            'tier' => '25K',
        ]);
        LoyaltyMembership::factory()->create([
            'user_id' => $erik->id,
            'loyalty_program_id' => $program->id,
            'tier' => '50K',
        ]);

        Livewire::test(BookTravel::class)
            ->fillForm([
                'category' => BookingCategory::Flight,
                'vendor' => 'Air Canada',
                'cash_price' => 400,
                'cabin' => BookingCabin::Economy,
            ])
            ->call('rank')
            ->assertSet('showLoyaltySelect', true)
            ->assertSet('loyaltyOptions', function (array $options): bool {
                $labels = collect($options)->pluck('label');

                return $labels->contains('Amy · Air Canada Aeroplan 25K')
                    && $labels->contains('Erik · Air Canada Aeroplan 50K');
            });
    }
}
