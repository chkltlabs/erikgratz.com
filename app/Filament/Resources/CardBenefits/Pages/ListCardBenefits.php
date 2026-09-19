<?php

declare(strict_types=1);

namespace App\Filament\Resources\CardBenefits\Pages;

use App\Filament\Resources\CardBenefits\CardBenefitResource;
use App\Services\TravelWallet\BenefitRefresher;
use App\Services\TravelWallet\UnusedBenefitFeed;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListCardBenefits extends ListRecords
{
    protected static string $resource = CardBenefitResource::class;

    public bool $showAllLocations = false;

    public function mount(): void
    {
        app(BenefitRefresher::class)->refreshAll();

        parent::mount();
    }

    public function updatedActiveTab(): void
    {
        parent::updatedActiveTab();

        $this->resetTable();
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'due' => Tab::make('Due'),
            'auto' => Tab::make('Assumed captured'),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleLocations')
                ->label(fn (): string => $this->showAllLocations
                    ? 'Hide location-only benefits'
                    : 'Show all locations')
                ->icon(Heroicon::OutlinedMapPin)
                ->color('gray')
                ->visible(fn (): bool => ! $this->isAutoTab())
                ->action(function (): void {
                    $this->showAllLocations = ! $this->showAllLocations;
                    $this->resetTable();
                }),
        ];
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return $this->isAutoTab()
            ? app(UnusedBenefitFeed::class)->autoQuery()
            : app(UnusedBenefitFeed::class)->dueQuery($this->showAllLocations);
    }

    public function isAutoTab(): bool
    {
        return $this->activeTab === 'auto';
    }
}
