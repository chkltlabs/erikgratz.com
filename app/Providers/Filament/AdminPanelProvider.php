<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\IncomeReconciliation;
use App\Filament\Widgets\MonthlyBudgetStatus;
use App\Filament\Widgets\PendingReviewTransactions;
use App\Filament\Widgets\SpendingCategoryChart;
use App\Filament\Widgets\SpendingTrendsChart;
use App\Filament\Widgets\UncategorizedTransactions;
use App\Filament\Widgets\PointsByProgram;
use Filament\Pages\Dashboard;
use App\Filament\Resources\CardResource\Widgets\SpentPayingSaving;
use App\Filament\Resources\LoanAgainstSavingsResource\Widgets\LoansDue;
use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\CardWidget;
use App\Filament\Widgets\NetWorthStats;
use App\Filament\Widgets\PastStatsChart;
use App\Filament\Widgets\SpendsThisMonth;
use App\Filament\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('images/favicon_io/favicon-32x32.png'))
//            ->colors([
// //                'primary' => Color::Amber,
//            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
//            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                SpentPayingSaving::class,
                CardWidget::class,
                AccountWidget::class,
                LoansDue::class,
                PointsByProgram::class,
                SpendsThisMonth::class,
                PastStatsChart::class,
//                IncomeReconciliation::class,
//                PendingReviewTransactions::class,
//                UncategorizedTransactions::class,
//                MonthlyBudgetStatus::class,
//                SpendingCategoryChart::class,
//                SpendingTrendsChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
//            ->sidebarWidth('12%')
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])->viteTheme('resources/css/filament/admin/theme.css');
    }
}
