<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\LoanAgainstSavingsResource\Widgets\LoansDue;
use App\Models\LoanAgainstSavings;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoansDueWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function it_loads_with_the_page_and_lists_open_loans(): void
    {
        $this->assertSame('on-load', LoansDue::getDefaultProperties()['lazy']);
        $this->assertContains(LoansDue::class, filament()->getPanel('admin')->getWidgets());

        LoanAgainstSavings::factory()->create([
            'reason' => 'Emergency fund',
            'balance' => 250.5,
            'is_paid' => false,
            'paid_on' => '2026-05-20',
        ]);

        Livewire::test(LoansDue::class)
            ->assertSuccessful()
            ->assertSee('Emergency fund')
            ->assertSee('$250.50');
    }
}
