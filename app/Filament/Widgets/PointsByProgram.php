<?php

namespace App\Filament\Widgets;

use App\Services\TravelWallet\PointsByProgramTotals;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PointsByProgram extends StatsOverviewWidget
{
    public string $household = 'total';

    protected ?string $heading = 'Points';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('household')
                    ->hiddenLabel()
                    ->options(fn (): array => app(PointsByProgramTotals::class)->householdOptions())
                    ->selectablePlaceholder(false)
                    ->live(),
                $this->getSectionContentComponent(),
            ]);
    }

    public function updatedHousehold(): void
    {
        $this->cachedStats = null;
    }

    protected function getStats(): array
    {
        $stats = [];

        foreach (app(PointsByProgramTotals::class)->forHousehold($this->household) as $row) {
            $stats[] = Stat::make($row['label'], number_format($row['total']))
                ->description($this->sourceDescription($row))
                ->color($this->sourceColor($row));
        }

        return $stats;
    }

    /**
     * @param  array{cards: int, loyalty: int}  $row
     */
    private function sourceDescription(array $row): string
    {
        $parts = [];

        if ($row['cards'] > 0) {
            $parts[] = 'Cards '.number_format($row['cards']);
        }
        if ($row['loyalty'] > 0) {
            $parts[] = 'Loyalty '.number_format($row['loyalty']);
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  array{cards: int, loyalty: int}  $row
     */
    private function sourceColor(array $row): string
    {
        if ($row['cards'] > 0 && $row['loyalty'] > 0) {
            return 'gray';
        }

        return $row['loyalty'] > 0 ? 'warning' : 'info';
    }
}
