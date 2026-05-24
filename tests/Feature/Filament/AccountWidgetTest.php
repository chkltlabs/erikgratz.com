<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AccountWidget;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountWidgetTest extends TestCase
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
        $account = Account::factory()->create(['balance' => 1500]);

        Livewire::test(AccountWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $account->getKey(), '1500 - 200');

        $this->assertSame(1300.0, (float) $account->fresh()->balance);
    }

    #[Test]
    public function it_rejects_invalid_expression_without_updating_balance(): void
    {
        $account = Account::factory()->create(['balance' => 1500]);

        $response = Livewire::test(AccountWidget::class)
            ->call('updateTableColumnState', 'balance', (string) $account->getKey(), '1500 - abc');

        $response->assertReturned(fn (mixed $returned): bool => is_array($returned)
            && array_key_exists('error', $returned)
            && str_contains($returned['error'], 'valid number or math expression'));
        $this->assertSame(1500.0, (float) $account->fresh()->balance);
    }
}
