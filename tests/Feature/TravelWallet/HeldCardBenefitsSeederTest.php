<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitTrackingMode;
use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Enums\ResetPeriod;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\LoyaltyMembership;
use Database\Seeders\TravelWalletCatalogSeeder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeldCardBenefitsSeederTest extends TestCase
{
    #[Test]
    public function seeds_current_benefits_onto_held_cards_by_name(): void
    {
        $names = [
            'Sapphire Reserve (Amy)',
            'Sapphire Preferred (Erik)',
            'C1 V X (Erik)',
            'C1 V X (Amy)',
            'Amex Green (Amy)',
            'Amex Green (Erik)',
            'Amex Plat (Erik)',
            'Chase BA (Erik)',
            'Chase BA (Amy)',
            'Aer Lingus (Erik)',
            'Chase Aeroplan (Amy)',
            'Chase Ink Preferred (Erik)',
        ];

        foreach ($names as $name) {
            Card::factory()->create(['name' => $name]);
        }

        $this->seed(TravelWalletCatalogSeeder::class);

        $this->assertCount(13, $this->benefitsNamed('Sapphire Reserve (Amy)'));
        $this->assertCount(4, $this->benefitsNamed('Sapphire Preferred (Erik)'));
        $this->assertCount(5, $this->benefitsNamed('C1 V X (Erik)'));
        $this->assertCount(5, $this->benefitsNamed('C1 V X (Amy)'));
        $this->assertCount(1, $this->benefitsNamed('Amex Green (Amy)'));
        $this->assertCount(1, $this->benefitsNamed('Amex Green (Erik)'));
        $this->assertCount(14, $this->benefitsNamed('Amex Plat (Erik)'));
        $this->assertCount(2, $this->benefitsNamed('Chase BA (Erik)'));
        $this->assertCount(2, $this->benefitsNamed('Chase BA (Amy)'));
        $this->assertCount(2, $this->benefitsNamed('Aer Lingus (Erik)'));
        $this->assertCount(2, $this->benefitsNamed('Chase Aeroplan (Amy)'));
        $this->assertCount(0, $this->benefitsNamed('Chase Ink Preferred (Erik)'));

        $this->assertTrue(
            $this->benefitsNamed('Amex Plat (Erik)')->contains('benefit', '$100 Resy credit')
        );
        $resy = $this->benefitsNamed('Amex Plat (Erik)')->firstWhere('benefit', '$100 Resy credit');
        $this->assertTrue($resy->reset_period->is(ResetPeriod::Quarterly));
        $this->assertEquals(100.0, $resy->value);
        $uber = $this->benefitsNamed('Amex Plat (Erik)')->firstWhere('benefit', '$15 Uber Cash');
        $this->assertTrue($uber->reset_period->is(ResetPeriod::Monthly));
        $this->assertTrue($uber->tracking_mode->is(BenefitTrackingMode::Auto));
        $this->assertEquals(15.0, $uber->value);
        $this->assertTrue(
            $this->benefitsNamed('C1 V X (Erik)')->contains('benefit', '$300 Capital One Travel credit')
        );

        foreach ([
            'Sapphire Reserve (Amy)' => 'Global Entry / TSA PreCheck / NEXUS',
            'Sapphire Preferred (Erik)' => 'Global Entry / TSA PreCheck / NEXUS',
            'C1 V X (Erik)' => 'Global Entry / TSA PreCheck',
            'C1 V X (Amy)' => 'Global Entry / TSA PreCheck',
            'Chase Aeroplan (Amy)' => 'Global Entry / TSA PreCheck / NEXUS',
        ] as $cardName => $benefitName) {
            $credit = $this->benefitsNamed($cardName)->firstWhere('benefit', $benefitName);
            $this->assertTrue($credit->reset_period->is(ResetPeriod::CalendarYearly), $cardName);
            $this->assertEquals(120.0, $credit->value, $cardName);
        }
        $plat = Card::query()->where('name', 'Amex Plat (Erik)')->first();
        $this->assertTrue(
            LoyaltyMembership::query()
                ->where('user_id', $plat->user_id)
                ->whereHas('conferredByCard', fn ($query) => $query->where('name', 'Amex Plat (Erik)'))
                ->whereHas('program', fn ($query) => $query->where('code', 'hilton'))
                ->exists()
        );
        $aeroplanCard = Card::query()->where('name', 'Chase Aeroplan (Amy)')->first();
        $this->assertTrue(
            LoyaltyMembership::query()
                ->where('user_id', $aeroplanCard->user_id)
                ->whereHas('program', fn ($query) => $query->where('code', 'aeroplan'))
                ->exists()
        );

        $fhr = $this->benefitsNamed('Amex Plat (Erik)')->firstWhere('benefit', '$300 FHR / Hotel Collection');
        $this->assertTrue($fhr->required_channel->is(BookingChannel::AmexTravel));
        $this->assertTrue(
            BookingPerk::query()->where('card_benefit_id', $fhr->id)->where('name', 'On-property credit')->exists()
        );
        $this->assertTrue(
            BookingPerk::query()
                ->whereHas('card', fn ($query) => $query->where('name', 'Chase Aeroplan (Amy)'))
                ->where('name', '15% off Air Canada awards')
                ->where('award_only', true)
                ->exists()
        );
        $this->assertTrue(
            CardEarningRate::query()
                ->whereHas('card', fn ($query) => $query->where('name', 'Sapphire Reserve (Amy)'))
                ->where('channel', BookingChannel::ChaseTravel)
                ->where('multiplier', 8)
                ->exists()
        );
        $this->assertTrue(
            BookingPerk::query()
                ->whereHas('program', fn ($query) => $query->where('code', 'hilton'))
                ->where('name', 'Complimentary breakfast for two')
                ->exists()
        );

        $baCredit = $this->benefitsNamed('Chase BA (Erik)')->firstWhere('benefit', '$600 reward-flight credits');
        $this->assertTrue($baCredit->award_only);
        $this->assertSame(3, $baCredit->quantity_total);
        $this->assertSame(['british airways', 'ba'], $baCredit->allowed_vendors);
        $this->assertEquals(100.0, $baCredit->max_apply_per_use['economy']);

        $edit = $this->benefitsNamed('Sapphire Reserve (Amy)')->firstWhere('benefit', '$500 The Edit credit');
        $this->assertSame(2, $edit->quantity_total);
        $this->assertEquals(250, $edit->max_apply_per_use['default']);

        $selectHotels = $this->benefitsNamed('Sapphire Reserve (Amy)')->firstWhere('benefit', '$250 select Chase Travel hotels');
        $this->assertContains('ihg', $selectHotels->allowed_vendors);

        $aeroplan = $this->benefitsNamed('Chase Aeroplan (Amy)')->firstWhere('benefit', '$50 Air Canada credit');
        $this->assertSame(['air canada', 'aeroplan'], $aeroplan->allowed_vendors);

        $airlineFee = $this->benefitsNamed('Amex Plat (Erik)')->firstWhere('benefit', '$200 airline fee credit');
        $this->assertTrue($airlineFee->applies_to->is(BenefitAppliesTo::Other));

        $platRates = CardEarningRate::query()
            ->whereHas('card', fn ($query) => $query->where('name', 'Amex Plat (Erik)'))
            ->where('category', BookingCategory::Flight)
            ->get();
        $this->assertTrue(
            $platRates->contains(
                fn (CardEarningRate $rate): bool => $rate->channel->is(BookingChannel::AmexTravel) && $rate->multiplier === 5.0 && $rate->vendor === null
            )
        );
        $this->assertTrue(
            $platRates->contains(
                fn (CardEarningRate $rate): bool => $rate->channel->is(BookingChannel::Direct) && $rate->multiplier === 5.0 && $rate->vendor === null
            )
        );
        $this->assertTrue(
            $platRates->contains(
                fn (CardEarningRate $rate): bool => $rate->channel->is(BookingChannel::Direct) && $rate->multiplier === 1.0 && $rate->vendor === 'expedia'
            )
        );
    }

    #[Test]
    public function seeding_twice_does_not_duplicate_benefits(): void
    {
        Card::factory()->create(['name' => 'Amex Plat (Erik)']);

        $this->seed(TravelWalletCatalogSeeder::class);
        $count = CardBenefit::query()->count();

        $this->seed(TravelWalletCatalogSeeder::class);

        $this->assertSame($count, CardBenefit::query()->count());
    }

    /**
     * @return Collection<int, CardBenefit>
     */
    private function benefitsNamed(string $cardName): Collection
    {
        return CardBenefit::query()
            ->whereHas('card', fn ($query) => $query->where('name', $cardName))
            ->get();
    }
}
