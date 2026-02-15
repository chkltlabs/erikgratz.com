<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\IncomeReconciliation;
use App\Filament\Widgets\MonthlyBudgetStatus;
use App\Filament\Widgets\PendingReviewTransactions;
use App\Filament\Widgets\SpendingCategoryChart;
use App\Filament\Widgets\SpendingTrendsChart;
use App\Filament\Widgets\UncategorizedTransactions;
use App\Models\PeriodicSpend;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\SimpleFinRule;
use App\Models\Spend;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

class SimpleFinWidgetsTest extends FilamentTestCase
{
    use DatabaseTransactions;

    public function test_income_reconciliation_widget_shows_correct_data(): void
    {
        $user = auth()->user();
        $user->update(['monthly_pay' => 5000]);

        $account = SimpleFinAccount::factory()->create(['user_id' => $user->id]);

        // Confirmed income this month
        SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
            'amount' => 3000,
            'posted' => now()->startOfMonth()->addDay(),
            'is_confirmed' => true,
        ]);

        // Unconfirmed income (should be ignored)
        SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
            'amount' => 1000,
            'posted' => now()->startOfMonth()->addDay(),
            'is_confirmed' => false,
        ]);

        // Confirmed income last month (should be ignored)
        SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
            'amount' => 2000,
            'posted' => now()->subMonth()->startOfMonth(),
            'is_confirmed' => true,
        ]);

        Livewire::test(IncomeReconciliation::class)
            ->assertSee('$5,000.00') // Expected
            ->assertSee('$3,000.00') // Actual
            ->assertSee('$-2,000.00') // Variance
            ->assertSee('Behind target');
    }

    public function test_monthly_budget_status_widget_shows_periodic_spends(): void
    {
        $periodicSpend = PeriodicSpend::factory()->create([
            'name' => 'Monthly Rent',
            'is_income' => false,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addYear(),
        ]);

        Livewire::test(MonthlyBudgetStatus::class)
            ->assertCanSeeTableRecords([$periodicSpend])
            ->assertTableColumnStateSet('name', 'Monthly Rent', record: $periodicSpend);
    }

    public function test_pending_review_transactions_widget_lists_unconfirmed_matches(): void
    {
        $spend = Spend::factory()->create();
        $transaction = SimpleFinTransaction::factory()->create([
            'spend_id' => $spend->id,
            'spend_type' => 'spend',
            'is_confirmed' => false,
            'description' => 'Pending Review Transaction',
        ]);

        Livewire::test(PendingReviewTransactions::class)
            ->assertCanSeeTableRecords([$transaction])
            ->assertTableActionExists('confirm')
            ->assertTableActionExists('assign')
            ->assertTableActionExists('reject')
            ->callTableAction('confirm', $transaction)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($transaction->refresh()->is_confirmed);

        // Test Reject
        $transaction->update(['is_confirmed' => false]);
        Livewire::test(PendingReviewTransactions::class)
            ->callTableAction('reject', $transaction)
            ->assertHasNoTableActionErrors();

        $transaction->refresh();
        $this->assertNull($transaction->spend_id);

        // Test Assign
        $transaction->update([
            'spend_id' => $spend->id,
            'spend_type' => 'spend',
            'is_confirmed' => false
        ]);
        Livewire::test(PendingReviewTransactions::class)
            ->callTableAction('assign', $transaction, [
                'spend_type' => 'periodic_spend',
                'spend_id' => PeriodicSpend::factory()->create()->id,
                'create_rule' => false,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('periodic_spend', $transaction->refresh()->spend_type);
        $this->assertTrue($transaction->is_confirmed);
    }

    public function test_uncategorized_transactions_widget_lists_unassigned_transactions(): void
    {
        $transaction = SimpleFinTransaction::factory()->create([
            'spend_id' => null,
            'description' => 'Uncategorized Transaction',
        ]);

        Livewire::test(UncategorizedTransactions::class)
            ->assertCanSeeTableRecords([$transaction])
            ->assertTableActionExists('assign')
            ->callTableAction('assign', $transaction, [
                'spend_type' => 'spend',
                'spend_id' => Spend::factory()->create()->id,
                'create_rule' => true,
                'rule_pattern' => 'Uncategorized',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($transaction->refresh()->spend_id);
        $this->assertTrue($transaction->is_confirmed);
        $this->assertDatabaseHas('simple_fin_rules', ['pattern' => 'Uncategorized']);
    }

    public function test_spending_category_chart_options(): void
    {
        $spend = Spend::factory()->create(['type' => 'housing']);
        $account = SimpleFinAccount::factory()->create(['user_id' => auth()->id()]);

        SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
            'amount' => -1500,
            'spend_id' => $spend->id,
            'spend_type' => 'spend',
            'is_confirmed' => true,
            'posted' => now()->startOfMonth()->addDay(),
        ]);

        $component = Livewire::test(SpendingCategoryChart::class);
        $instance = $component->instance();
        $reflection = new \ReflectionMethod($instance, 'getOptions');
        $reflection->setAccessible(true);
        $options = $reflection->invoke($instance);

        $this->assertContains(1500.0, $options['series']);
        $this->assertContains('housing', $options['labels']);
    }

    public function test_spending_trends_chart_options(): void
    {
        $account = SimpleFinAccount::factory()->create(['user_id' => auth()->id()]);

        SimpleFinTransaction::factory()->create([
            'simple_fin_account_id' => $account->id,
            'amount' => -1000,
            'is_confirmed' => true,
            'posted' => now()->startOfMonth()->addDay(),
        ]);

        $component = Livewire::test(SpendingTrendsChart::class);
        $instance = $component->instance();
        $reflection = new \ReflectionMethod($instance, 'getOptions');
        $reflection->setAccessible(true);
        $options = $reflection->invoke($instance);

        $this->assertNotEmpty($options['series'][0]['data']);
        $this->assertContains(now()->format('M Y'), $options['xaxis']['categories']);
    }
}
