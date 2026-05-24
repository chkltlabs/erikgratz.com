<?php

namespace Tests\Unit\Filament;

use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpentPayingSavingTooltipTest extends TestCase
{
    #[Test]
    public function format_unspent_tooltip_lists_items_and_total(): void
    {
        $tooltip = SpentPayingSaving::formatUnspentTooltip([
            ['name' => 'Rent', 'amount' => 1200.5],
            ['name' => 'Internet', 'amount' => 80],
        ], 1280.5);

        $this->assertStringContainsString('Rent — $1,200.50', $tooltip);
        $this->assertStringContainsString('Internet — $80.00', $tooltip);
        $this->assertStringContainsString('Total: $1,280.50', $tooltip);
    }

    #[Test]
    public function format_unspent_tooltip_handles_empty_items(): void
    {
        $this->assertSame(
            'No planned unpaid spends',
            SpentPayingSaving::formatUnspentTooltip([], 0),
        );
    }
}
