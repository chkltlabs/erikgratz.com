<?php

declare(strict_types=1);

namespace Tests\Unit\TravelWallet;

use App\Enums\BenefitResetAnchor;
use App\Enums\ResetPeriod;
use App\Models\Card;
use App\Models\CardBenefit;
use App\Services\TravelWallet\BenefitWindowCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BenefitWindowCalculatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function no_reset_has_no_end(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::NoReset,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now());

        $this->assertNull($window['start']);
        $this->assertNull($window['end']);
    }

    #[Test]
    public function calendar_yearly_runs_jan_to_jan(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::CalendarYearly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-01-01', $window['start']->toDateString());
        $this->assertSame('2027-01-01', $window['end']->toDateString());
    }

    #[Test]
    public function monthly_statement_uses_card_statement_day(): void
    {
        $card = Card::factory()->create([
            'statement_date' => 15,
        ]);
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Statement,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-09-15', $window['start']->toDateString());
        $this->assertSame('2026-10-15', $window['end']->toDateString());
    }

    #[Test]
    public function calendar_monthly_runs_first_to_first(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::Monthly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-09-01', $window['start']->toDateString());
        $this->assertSame('2026-10-01', $window['end']->toDateString());
    }

    #[Test]
    public function renewal_yearly_uses_card_open_anniversary(): void
    {
        $card = Card::factory()->create([
            'date_opened' => '2024-03-10',
        ]);
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'reset_period' => ResetPeriod::RenewalYearly,
            'reset_anchor' => BenefitResetAnchor::CardAnniversary,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-03-10', $window['start']->toDateString());
        $this->assertSame('2027-03-10', $window['end']->toDateString());
    }

    #[Test]
    public function calendar_quarterly_uses_jul_to_oct_in_september(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::Quarterly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-07-01', $window['start']->toDateString());
        $this->assertSame('2026-10-01', $window['end']->toDateString());
    }

    #[Test]
    public function calendar_quarterly_starts_a_new_window_on_the_reset_day(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::Quarterly,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 4, 1));

        $this->assertSame('2026-04-01', $window['start']->toDateString());
        $this->assertSame('2026-07-01', $window['end']->toDateString());
    }

    #[Test]
    public function calendar_semi_annual_uses_jul_to_jan_in_september(): void
    {
        $benefit = CardBenefit::factory()->create([
            'reset_period' => ResetPeriod::SemiAnnual,
            'reset_anchor' => BenefitResetAnchor::Calendar,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-07-01', $window['start']->toDateString());
        $this->assertSame('2027-01-01', $window['end']->toDateString());
    }

    #[Test]
    public function anniversary_quarterly_steps_from_the_open_date(): void
    {
        $card = Card::factory()->create([
            'date_opened' => '2024-03-10',
        ]);
        $benefit = CardBenefit::factory()->create([
            'card_id' => $card->id,
            'reset_period' => ResetPeriod::Quarterly,
            'reset_anchor' => BenefitResetAnchor::CardAnniversary,
        ]);

        $window = app(BenefitWindowCalculator::class)->window($benefit, now()->setDate(2026, 9, 17));

        $this->assertSame('2026-09-10', $window['start']->toDateString());
        $this->assertSame('2026-12-10', $window['end']->toDateString());
    }
}
