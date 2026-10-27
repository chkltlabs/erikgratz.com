<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\Period;
use App\Filament\Widgets\SpendsThisMonth;
use App\Models\Card;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpendsThisMonthWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-15');
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_loads_with_the_page_and_lists_this_months_spends(): void
    {
        $this->assertSame('on-load', SpendsThisMonth::getDefaultProperties()['lazy']);
        $this->assertContains(SpendsThisMonth::class, filament()->getPanel('admin')->getWidgets());

        $card = Card::factory()->create(['name' => 'Venture X']);

        $rent = PeriodicSpend::factory()->create([
            'name' => 'May Rent',
            'period' => Period::Monthly,
        ]);
        $insurance = PeriodicSpend::factory()->create([
            'name' => 'May Insurance',
            'period' => Period::Yearly,
        ]);
        $flight = Spend::factory()->bare()->noPayments()->create([
            'name' => 'May Flight',
        ]);

        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $rent->id,
            'amount' => 1200,
            'is_paid' => false,
            'paid_on' => '2026-05-10',
            'card_id' => $card->id,
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(PeriodicSpend::class),
            'spend_id' => $insurance->id,
            'amount' => 90,
            'is_paid' => false,
            'paid_on' => '2026-05-20',
            'card_id' => $card->id,
        ]);
        Payment::factory()->create([
            'spend_type' => getMorphAliasForClass(Spend::class),
            'spend_id' => $flight->id,
            'amount' => 40,
            'is_paid' => false,
            'paid_on' => '2026-05-15',
            'card_id' => $card->id,
        ]);

        Livewire::test(SpendsThisMonth::class)
            ->assertSuccessful()
            ->assertSee('May Rent')
            ->assertSee('May Insurance')
            ->assertSee('May Flight')
            ->assertSee('Venture X')
            ->assertSee('Unpaid');
    }
}
