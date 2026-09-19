<?php

declare(strict_types=1);

namespace Tests\Feature\TravelWallet;

use App\Enums\BenefitAppliesTo;
use App\Models\Activity;
use App\Models\BenefitUsage;
use App\Models\BookingIntent;
use App\Models\BookingPerk;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Models\CardEarningRate;
use App\Models\EarningPromotion;
use App\Models\LoanAgainstSavings;
use App\Models\LoyaltyMembership;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\PointRedemption;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardDeletionCascadeTest extends TestCase
{
    #[Test]
    public function deleting_a_card_wipes_wallet_rows_but_keeps_activity_payment_and_redemption(): void
    {
        $activity = Activity::factory()->create();
        $card = Card::factory()->create();
        $benefit = CardBenefit::factory()->create(['card_id' => $card->id]);
        $usage = BenefitUsage::factory()->create([
            'card_benefit_id' => $benefit->id,
            'activity_id' => $activity->id,
        ]);
        $payment = Payment::factory()->create(['card_id' => $card->id]);
        $redemption = PointRedemption::factory()->create(['activity_id' => $activity->id]);
        $loan = LoanAgainstSavings::factory()->create(['card_id' => $card->id]);
        $rate = CardEarningRate::factory()->create(['card_id' => $card->id]);
        $cardPerk = BookingPerk::factory()->create(['card_id' => $card->id]);
        $benefitPerk = BookingPerk::factory()->create([
            'card_id' => null,
            'card_benefit_id' => $benefit->id,
        ]);
        $promo = EarningPromotion::factory()->create(['card_id' => $card->id]);
        $program = LoyaltyProgram::factory()->create();
        $membership = LoyaltyMembership::factory()->create([
            'loyalty_program_id' => $program->id,
            'conferred_by_card_id' => $card->id,
        ]);
        $membershipPerk = BookingPerk::factory()->create([
            'card_id' => null,
            'loyalty_membership_id' => $membership->id,
            'applies_to' => BenefitAppliesTo::Hotel,
        ]);
        $intent = BookingIntent::factory()->create(['activity_id' => $activity->id]);

        $card->delete();

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
        $this->assertDatabaseMissing('card_benefits', ['id' => $benefit->id]);
        $this->assertDatabaseMissing('benefit_usages', ['id' => $usage->id]);
        $this->assertDatabaseMissing('card_earning_rates', ['id' => $rate->id]);
        $this->assertDatabaseMissing('booking_perks', ['id' => $cardPerk->id]);
        $this->assertDatabaseMissing('booking_perks', ['id' => $benefitPerk->id]);
        $this->assertDatabaseMissing('booking_perks', ['id' => $membershipPerk->id]);
        $this->assertDatabaseMissing('earning_promotions', ['id' => $promo->id]);
        $this->assertDatabaseMissing('loyalty_memberships', ['id' => $membership->id]);

        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'card_id' => null,
        ]);
        $this->assertDatabaseHas('point_redemptions', ['id' => $redemption->id]);
        $this->assertDatabaseHas('loan_against_savings', ['id' => $loan->id]);
        $this->assertDatabaseHas('booking_intents', [
            'id' => $intent->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertDatabaseHas('loyalty_programs', ['id' => $program->id]);
    }
}
