<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Ai\Agents\BookingExplainerAgent;
use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitValueKind;
use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Enums\LoyaltyKind;
use App\Enums\PointsProgram;
use App\Models\BenefitUsage;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\TransferBonus;
use App\Models\TransferRoute;
use App\Models\User;
use App\Services\TravelWallet\BookingAdvisor;
use App\Services\TravelWallet\BookingCombo;
use App\Services\TravelWallet\BookingRequest;
use Laravel\Ai\Contracts\Conversational;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingAdvisorTest extends TestCase
{
    #[Test]
    public function explainer_agent_is_not_conversational(): void
    {
        $this->assertNotInstanceOf(Conversational::class, app(BookingExplainerAgent::class));
    }

    #[Test]
    public function unsatisfied_sub_outranks_a_normal_earn_card(): void
    {
        $subCard = Card::factory()->create([
            'name' => 'New SUB card',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subWeek()->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 4000,
            'balance' => 0,
            'pending' => 0,
        ]);
        $oldCard = Card::factory()->create([
            'name' => 'Old UR card',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_period' => '+3 months',
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            cashPrice: 400,
        ));

        $subScore = collect($ranking)->where('cardId', $subCard->id)->max(fn (BookingCombo $combo): float => $combo->score);
        $oldScore = collect($ranking)->where('cardId', $oldCard->id)->max(fn (BookingCombo $combo): float => $combo->score);

        $subCombo = collect($ranking)->first(fn (BookingCombo $combo): bool => $combo->cardId === $subCard->id);

        $this->assertNotNull($subScore);
        $this->assertNotNull($oldScore);
        $this->assertGreaterThan($oldScore, $subScore);
        $this->assertNotNull($subCombo);
        $this->assertTrue(
            collect($subCombo->reasons)->contains(fn (string $reason): bool => str_contains($reason, 'Signup bonus'))
        );
    }

    #[Test]
    public function hotel_fhr_combo_uses_amex_travel_package_and_hilton_perks(): void
    {
        $plat = Card::factory()->create([
            'name' => 'Amex Plat',
            'points_program' => PointsProgram::AmExMemberRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        $fhr = CardBenefit::factory()->create([
            'card_id' => $plat->id,
            'benefit' => '$300 FHR / Hotel Collection',
            'value' => 300,
            'applies_to' => BenefitAppliesTo::Hotel,
            'required_channel' => BookingChannel::AmexTravel,
        ]);
        BookingPerk::factory()->create([
            'card_id' => null,
            'card_benefit_id' => $fhr->id,
            'name' => 'On-property credit',
            'decision_value' => 100,
            'applies_to' => BenefitAppliesTo::Hotel,
            'channel' => BookingChannel::AmexTravel,
        ]);
        $hilton = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Hotel,
            'name' => 'Hilton Honors',
            'code' => 'hilton',
        ]);
        BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_program_id' => $hilton->id,
            'min_tier' => 'Gold',
            'name' => 'Complimentary breakfast for two',
            'decision_value' => 40,
            'applies_to' => BenefitAppliesTo::Hotel,
        ]);
        LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $hilton->id,
            'conferred_by_card_id' => $plat->id,
            'tier' => 'Gold',
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            vendor: 'Hilton',
            cashPrice: 400,
        ));

        $combo = collect($ranking)->first(
            fn (BookingCombo $row): bool => collect($row->credits)->contains('id', $fhr->id)
        );

        $this->assertNotNull($combo);
        $this->assertSame($plat->id, $combo->cardId);
        $this->assertTrue($combo->channel->is(BookingChannel::AmexTravel));
        $this->assertTrue(collect($combo->creditPerks)->contains('name', 'On-property credit'));
        $this->assertTrue(collect($combo->loyaltyPerks)->contains('name', 'Complimentary breakfast for two'));
        $this->assertNotNull($combo->earn);
    }

    #[Test]
    public function aeroplan_award_perks_beat_a_naive_transfer(): void
    {
        $aeroplanCard = Card::factory()->create([
            'name' => 'Aeroplan card',
            'points_program' => PointsProgram::Aeroplan,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        BookingPerk::factory()->create([
            'card_id' => $aeroplanCard->id,
            'name' => '15% off Air Canada awards',
            'decision_value' => 0,
            'applies_to' => BenefitAppliesTo::Flight,
            'award_only' => true,
        ]);
        BookingPerk::factory()->create([
            'card_id' => $aeroplanCard->id,
            'name' => '10% extra transfer bonus',
            'decision_value' => 0,
            'applies_to' => BenefitAppliesTo::Flight,
            'award_only' => true,
        ]);
        $source = Card::factory()->create([
            'name' => 'CSR',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'points_balance' => 200000,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Aeroplan',
            'code' => 'aeroplan',
        ]);
        $route = TransferRoute::factory()->create([
            'from_program' => PointsProgram::ChaseUltimateRewards,
            'loyalty_program_id' => $program->id,
        ]);
        TransferBonus::factory()->create([
            'transfer_route_id' => $route->id,
            'bonus_percent' => 30,
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 800,
            awardQuotes: [[
                'points_program' => PointsProgram::Aeroplan,
                'program_code' => 'aeroplan',
                'points' => 50000,
                'cash' => 0,
            ]],
        ));

        $award = collect($ranking)->first(
            fn (BookingCombo $combo): bool => $combo->isAward && $combo->transferRouteId === $route->id
        );

        $this->assertNotNull($award);
        $this->assertSame($source->id, $award->cardId);
        $this->assertLessThan(50000 / 1.3, $award->meta['source_points']);
        $this->assertTrue(collect($award->reasons)->contains(fn (string $reason): bool => str_contains($reason, '15%')));
        $this->assertTrue(collect($award->reasons)->contains(fn (string $reason): bool => str_contains($reason, 'extra transfer')));
    }

    #[Test]
    public function empty_vendor_omits_loyalty_perks_until_membership_is_selected(): void
    {
        $card = Card::factory()->create([
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

        $without = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            cashPrice: 300,
        ));
        $with = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            cashPrice: 300,
            loyaltyMembershipId: $membership->id,
        ));

        $this->assertTrue(collect($without)->every(fn (BookingCombo $combo): bool => $combo->loyaltyPerks === []));
        $this->assertTrue(collect($with)->contains(
            fn (BookingCombo $combo): bool => collect($combo->loyaltyPerks)->contains('name', 'Hyatt late checkout')
        ));
    }

    #[Test]
    public function two_household_aeroplan_accounts_require_an_explicit_pick(): void
    {
        $amy = User::factory()->create(['name' => 'Amy']);
        $erik = User::factory()->create(['name' => 'Erik']);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Air Canada Aeroplan',
            'code' => 'aeroplan',
        ]);
        $amyMembership = LoyaltyMembership::factory()->create([
            'user_id' => $amy->id,
            'loyalty_program_id' => $program->id,
            'tier' => '25K',
            'points_balance' => 8000,
        ]);
        $erikMembership = LoyaltyMembership::factory()->create([
            'user_id' => $erik->id,
            'loyalty_program_id' => $program->id,
            'tier' => '50K',
            'points_balance' => 120000,
        ]);
        BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_membership_id' => $amyMembership->id,
            'name' => 'Amy 25K eUpgrade',
            'decision_value' => 25,
            'applies_to' => BenefitAppliesTo::Flight,
        ]);
        Card::factory()->create([
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);

        $advisor = app(BookingAdvisor::class);
        $request = new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 400,
        );

        $this->assertTrue($advisor->needsLoyaltySelect($request));
        $this->assertNull($advisor->resolveLoyalty($request));
        $labels = collect($advisor->loyaltyOptions($request))->pluck('label');
        $this->assertTrue($labels->contains('Amy · Air Canada Aeroplan 25K'));
        $this->assertTrue($labels->contains('Erik · Air Canada Aeroplan 50K'));

        $withAmy = $advisor->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 400,
            loyaltyMembershipId: $amyMembership->id,
        ));
        $withErik = $advisor->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 400,
            loyaltyMembershipId: $erikMembership->id,
        ));

        $this->assertTrue(collect($withAmy)->contains(
            fn (BookingCombo $combo): bool => collect($combo->loyaltyPerks)->contains('name', 'Amy 25K eUpgrade')
        ));
        $this->assertTrue(collect($withErik)->every(
            fn (BookingCombo $combo): bool => ! collect($combo->loyaltyPerks)->contains('name', 'Amy 25K eUpgrade')
        ));
    }

    #[Test]
    public function a_single_matching_membership_auto_attaches(): void
    {
        $amy = User::factory()->create(['name' => 'Amy']);
        $program = LoyaltyProgram::factory()->create([
            'kind' => LoyaltyKind::Airline,
            'name' => 'Air Canada Aeroplan',
            'code' => 'aeroplan',
        ]);
        $membership = LoyaltyMembership::factory()->create([
            'user_id' => $amy->id,
            'loyalty_program_id' => $program->id,
            'tier' => '25K',
        ]);
        BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_membership_id' => $membership->id,
            'name' => 'Amy 25K eUpgrade',
            'decision_value' => 25,
            'applies_to' => BenefitAppliesTo::Flight,
        ]);
        Card::factory()->create([
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);

        $advisor = app(BookingAdvisor::class);
        $request = new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 400,
        );

        $this->assertFalse($advisor->needsLoyaltySelect($request));
        $this->assertSame($membership->id, $advisor->resolveLoyalty($request)?->id);
        $this->assertTrue(collect($advisor->recommend($request))->contains(
            fn (BookingCombo $combo): bool => collect($combo->loyaltyPerks)->contains('name', 'Amy 25K eUpgrade')
        ));
    }

    #[Test]
    public function flag_and_ignore_rows_are_not_expendable_credits(): void
    {
        $card = Card::factory()->create([
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        $flag = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => 'Lounge access',
            'value' => 0,
            'value_kind' => BenefitValueKind::Flag,
            'tracking_mode' => BenefitTrackingMode::Ignore,
            'applies_to' => BenefitAppliesTo::Flight,
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            cashPrice: 400,
        ));

        $this->assertTrue(collect($ranking)->every(
            fn (BookingCombo $combo): bool => ! collect($combo->credits)->contains('id', $flag->id)
        ));
    }

    #[Test]
    public function returns_at_most_five_combos_and_chase_travel_eight_x_outranks_default(): void
    {
        $csr = Card::factory()->create([
            'name' => 'CSR',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        CardEarningRate::factory()->create([
            'card_id' => $csr->id,
            'category' => BookingCategory::Hotel,
            'channel' => BookingChannel::ChaseTravel,
            'multiplier' => 8,
        ]);
        $other = Card::factory()->create([
            'name' => 'Basic card',
            'points_program' => PointsProgram::Unknown,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        foreach (range(1, 6) as $i) {
            Card::factory()->create([
                'name' => 'Extra '.$i,
                'points_program' => PointsProgram::CitiThankYou,
                'date_opened' => now()->subYears(2)->toDateString(),
                'points_bonus_spend' => 1,
                'balance' => 5000,
                'pending' => 0,
            ]);
        }

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            cashPrice: 400,
        ));

        $this->assertLessThanOrEqual(5, count($ranking));
        $chase = collect($ranking)->first(
            fn (BookingCombo $combo): bool => $combo->cardId === $csr->id && $combo->channel->is(BookingChannel::ChaseTravel)
        );
        $basic = collect($ranking)->first(fn (BookingCombo $combo): bool => $combo->cardId === $other->id);

        $this->assertNotNull($chase);
        $this->assertGreaterThan($basic?->score ?? 0, $chase->score);
    }

    #[Test]
    public function lifestyle_credits_are_not_applied_to_travel_bookings(): void
    {
        $plat = Card::factory()->create([
            'name' => 'Amex Plat',
            'points_program' => PointsProgram::AmExMemberRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        CardBenefit::factory()->create([
            'card_id' => $plat->id,
            'benefit' => '$300 Equinox credit',
            'value' => 300,
            'applies_to' => BenefitAppliesTo::Other,
        ]);
        $csr = Card::factory()->create([
            'name' => 'Sapphire Reserve',
            'points_program' => PointsProgram::ChaseUltimateRewards,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
        CardBenefit::factory()->create([
            'card_id' => $csr->id,
            'benefit' => '$300 travel credit',
            'value' => 300,
            'applies_to' => BenefitAppliesTo::Any,
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            cashPrice: 400,
        ));

        $this->assertTrue(
            collect($ranking)->every(
                fn (BookingCombo $combo): bool => collect($combo->credits)->every(
                    fn (array $credit): bool => ($credit['name'] ?? '') !== '$300 Equinox credit'
                )
            )
        );
        $this->assertTrue(
            collect($ranking)->contains(
                fn (BookingCombo $combo): bool => collect($combo->credits)->contains('name', '$300 travel credit')
            )
        );
    }

    #[Test]
    public function ba_reward_credit_is_omitted_from_cash_ranks(): void
    {
        $this->baCardWithRewardCredit();

        foreach (['Air Canada', 'British Airways'] as $vendor) {
            $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
                category: BookingCategory::Flight(),
                vendor: $vendor,
                cashPrice: 400,
            ));

            $this->assertTrue(
                collect($ranking)->every(
                    fn (BookingCombo $combo): bool => collect($combo->credits)->every(
                        fn (array $credit): bool => ($credit['name'] ?? '') !== '$600 reward-flight credits'
                    )
                ),
                $vendor
            );
        }
    }

    #[Test]
    public function ba_reward_credit_applies_cabin_caps_to_award_quote_cash(): void
    {
        $credit = $this->baCardWithRewardCredit();

        $economy = $this->awardCreditOn(app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'British Airways',
            cashPrice: 400,
            awardQuotes: [[
                'points_program' => PointsProgram::Avios,
                'program_code' => 'ba',
                'points' => 20000,
                'cash' => 180,
            ]],
            cabin: BookingCabin::Economy(),
        )), $credit->id);

        $business = $this->awardCreditOn(app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'British Airways',
            cashPrice: 400,
            awardQuotes: [[
                'points_program' => PointsProgram::Avios,
                'program_code' => 'ba',
                'points' => 20000,
                'cash' => 250,
            ]],
            cabin: BookingCabin::Business(),
        )), $credit->id);

        $this->assertSame(100.0, $economy['applied']);
        $this->assertSame(200.0, $business['applied']);
    }

    #[Test]
    public function ba_reward_credit_is_omitted_after_three_uses(): void
    {
        $credit = $this->baCardWithRewardCredit();

        foreach (range(1, 3) as $i) {
            BenefitUsage::factory()->create([
                'card_benefit_id' => $credit->id,
                'amount' => 100,
                'quantity' => 1,
            ]);
        }

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'British Airways',
            cashPrice: 400,
            awardQuotes: [[
                'points_program' => PointsProgram::Avios,
                'program_code' => 'ba',
                'points' => 20000,
                'cash' => 180,
            ]],
        ));

        $this->assertTrue(collect($ranking)->every(
            fn (BookingCombo $combo): bool => ! collect($combo->credits)->contains('id', $credit->id)
        ));
    }

    #[Test]
    public function the_edit_applies_at_most_two_hundred_fifty_per_stay(): void
    {
        $csr = $this->settledCard('CSR', PointsProgram::ChaseUltimateRewards);
        $edit = CardBenefit::factory()->create([
            'card_id' => $csr->id,
            'benefit' => '$500 The Edit credit',
            'value' => 500,
            'applies_to' => BenefitAppliesTo::Hotel,
            'required_channel' => BookingChannel::ChaseTravel,
            'quantity_total' => 2,
            'max_apply_per_use' => ['default' => 250],
        ]);

        $first = $this->cashCreditOn(app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            vendor: 'The Edit',
            cashPrice: 600,
        )), $edit->id);

        $this->assertSame(250.0, $first['applied']);

        BenefitUsage::factory()->create([
            'card_benefit_id' => $edit->id,
            'amount' => 250,
            'quantity' => 1,
        ]);

        $second = $this->cashCreditOn(app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Hotel(),
            vendor: 'The Edit',
            cashPrice: 600,
        )), $edit->id);

        $this->assertSame(250.0, $second['applied']);
    }

    #[Test]
    public function unrestricted_travel_credit_can_apply_its_full_pool_to_one_booking(): void
    {
        $csr = $this->settledCard('CSR', PointsProgram::ChaseUltimateRewards);
        $travel = CardBenefit::factory()->create([
            'card_id' => $csr->id,
            'benefit' => '$300 travel credit',
            'value' => 300,
            'applies_to' => BenefitAppliesTo::Any,
        ]);

        $applied = $this->cashCreditOn(app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'United',
            cashPrice: 400,
        )), $travel->id);

        $this->assertSame(300.0, $applied['applied']);
    }

    #[Test]
    public function plat_earns_five_x_on_portal_or_airline_and_one_x_on_otas(): void
    {
        $plat = $this->settledCard('Amex Plat', PointsProgram::AmExMemberRewards);
        foreach ([
            ['channel' => BookingChannel::AmexTravel, 'multiplier' => 5, 'vendor' => ''],
            ['channel' => BookingChannel::Direct, 'multiplier' => 5, 'vendor' => ''],
            ['channel' => BookingChannel::Direct, 'multiplier' => 1, 'vendor' => 'expedia'],
            ['channel' => BookingChannel::Direct, 'multiplier' => 1, 'vendor' => 'priceline'],
        ] as $rate) {
            CardEarningRate::factory()->create([
                'card_id' => $plat->id,
                'category' => BookingCategory::Flight,
                ...$rate,
            ]);
        }

        $airline = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Air Canada',
            cashPrice: 400,
        ));
        $ota = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            vendor: 'Expedia',
            cashPrice: 400,
        ));

        $directAirline = collect($airline)->first(
            fn (BookingCombo $combo): bool => $combo->cardId === $plat->id && $combo->channel->is(BookingChannel::Direct)
        );
        $portalAirline = collect($airline)->first(
            fn (BookingCombo $combo): bool => $combo->cardId === $plat->id && $combo->channel->is(BookingChannel::AmexTravel)
        );
        $directOta = collect($ota)->first(
            fn (BookingCombo $combo): bool => $combo->cardId === $plat->id && $combo->channel->is(BookingChannel::Direct)
        );

        $this->assertSame(5.0, $directAirline?->earn['multiplier']);
        $this->assertSame(5.0, $portalAirline?->earn['multiplier']);
        $this->assertSame(1.0, $directOta?->earn['multiplier']);
    }

    #[Test]
    public function airline_incidental_credit_does_not_appear_on_a_flight_rank(): void
    {
        $plat = $this->settledCard('Amex Plat', PointsProgram::AmExMemberRewards);
        CardBenefit::factory()->create([
            'card_id' => $plat->id,
            'benefit' => '$200 airline fee credit',
            'value' => 200,
            'applies_to' => BenefitAppliesTo::Other,
        ]);

        $ranking = app(BookingAdvisor::class)->recommend(new BookingRequest(
            category: BookingCategory::Flight(),
            cashPrice: 400,
        ));

        $this->assertTrue(collect($ranking)->every(
            fn (BookingCombo $combo): bool => collect($combo->credits)->every(
                fn (array $credit): bool => ($credit['name'] ?? '') !== '$200 airline fee credit'
            )
        ));
    }

    private function settledCard(string $name, string $program): Card
    {
        return Card::factory()->create([
            'name' => $name,
            'points_program' => $program,
            'date_opened' => now()->subYears(2)->toDateString(),
            'points_bonus_spend' => 1,
            'balance' => 5000,
            'pending' => 0,
        ]);
    }

    private function baCardWithRewardCredit(): CardBenefit
    {
        $card = $this->settledCard('Chase BA', PointsProgram::Avios);

        return CardBenefit::factory()->create([
            'card_id' => $card->id,
            'benefit' => '$600 reward-flight credits',
            'value' => 600,
            'applies_to' => BenefitAppliesTo::Flight,
            'allowed_vendors' => ['british airways', 'ba'],
            'quantity_total' => 3,
            'award_only' => true,
            'max_apply_per_use' => [
                'economy' => 100,
                'premium_economy' => 100,
                'business' => 200,
                'first' => 200,
            ],
        ]);
    }

    /**
     * @return array{id: int, name: string, applied: float, remaining_after: float}
     */
    private function cashCreditOn(array $ranking, int $creditId): array
    {
        $combo = collect($ranking)->first(
            fn (BookingCombo $combo): bool => ! $combo->isAward && collect($combo->credits)->contains('id', $creditId)
        );

        $this->assertNotNull($combo);

        return collect($combo->credits)->firstWhere('id', $creditId);
    }

    /**
     * @return array{id: int, name: string, applied: float, remaining_after: float}
     */
    private function awardCreditOn(array $ranking, int $creditId): array
    {
        $combo = collect($ranking)->first(
            fn (BookingCombo $combo): bool => $combo->isAward && collect($combo->credits)->contains('id', $creditId)
        );

        $this->assertNotNull($combo);

        return collect($combo->credits)->firstWhere('id', $creditId);
    }
}
