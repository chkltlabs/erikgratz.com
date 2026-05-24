<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\CardWidget;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function it_saves_resolved_decimal_expression_for_balance(): void
    {
        $card = Card::factory()->create(['balance' => 1500]);

        Livewire::test(CardWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $card->getKey(), '1500 - 200');

        $this->assertSame(1300.0, (float) $card->fresh()->balance);
    }

    #[Test]
    public function it_saves_normalized_integer_expression_for_points_balance(): void
    {
        $card = Card::factory()->create(['points_balance' => 10000]);

        Livewire::test(CardWidget::class)
            ->call('updateTableColumnState', 'points_balance', (string) $card->getKey(), '10000 + 2500.6');

        $this->assertSame(12501, $card->fresh()->points_balance);
    }

    #[Test]
    public function it_rejects_invalid_expression_without_updating_balance(): void
    {
        $card = Card::factory()->create(['balance' => 1500]);

        $response = Livewire::test(CardWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $card->getKey(), '1500 - abc');

        $response->assertReturned(fn (mixed $returned): bool => is_array($returned)
            && array_key_exists('error', $returned)
            && str_contains($returned['error'], 'valid number or math expression'));
        $this->assertSame(1500.0, (float) $card->fresh()->balance);
    }
}
