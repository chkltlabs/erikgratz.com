<?php

namespace App\Filament\Widgets;

use App\Models\SimpleFin\SimpleFinTransaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class IncomeReconciliation extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $monthlyPay = (float) ($user->monthly_pay ?? 0);

        // Sum of confirmed income transactions for this month
        $actualIncome = (float) SimpleFinTransaction::query()
            ->whereHas('account', fn ($q) => $q->where('user_id', $user->id))
            ->where('is_confirmed', true)
            ->where('amount', '>', 0)
            ->whereBetween('posted', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $variance = $actualIncome - $monthlyPay;
        $varianceColor = $variance >= 0 ? 'success' : 'warning';
        $varianceIcon = $variance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            Stat::make('Expected Monthly Income', '$' . number_format($monthlyPay, 2)),
            Stat::make('Actual Confirmed Income', '$' . number_format($actualIncome, 2))
                ->description(now()->format('F Y'))
                ->color('info'),
            Stat::make('Income Variance', '$' . number_format($variance, 2))
                ->description($variance >= 0 ? 'Ahead of target' : 'Behind target')
                ->descriptionIcon($varianceIcon)
                ->color($varianceColor),
        ];
    }
}
