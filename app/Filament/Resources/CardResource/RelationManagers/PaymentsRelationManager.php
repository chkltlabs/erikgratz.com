<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use App\Models\Card;
use App\Models\Payment;
use Closure;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->record($this->getOwnerRecord())
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
                Section::make('Upcoming')
                    ->schema([
                        $this->paymentsRepeatableEntry(
                            'upcomingPayments',
                            fn (): Collection => $this->upcomingPayments(),
                            'No upcoming payments',
                        ),
                    ]),
                Section::make('Recently paid')
                    ->description('Paid in the last '.Payment::RECENTLY_PAID_WITHIN_DAYS.' days')
                    ->schema([
                        $this->paymentsRepeatableEntry(
                            'recentlyPaidPayments',
                            fn (): Collection => $this->recentlyPaidPayments(),
                            'No recently paid payments',
                        ),
                    ]),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components($this->paymentInfolistComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->upcomingOrRecentlyPaid()
                ->with('spend'))
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('spend.name')
                    ->label('Spend')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->money(fn (Payment $record): string => $record->currency->value),
                TextColumn::make('paid_on')
                    ->date()
                    ->placeholder('—'),
                IconColumn::make('is_paid')
                    ->boolean(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }

    /**
     * @return Collection<int, Payment>
     */
    protected function upcomingPayments(): Collection
    {
        return $this->card()
            ->payments()
            ->upcoming()
            ->with('spend')
            ->orderBy('paid_on')
            ->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    protected function recentlyPaidPayments(): Collection
    {
        return $this->card()
            ->payments()
            ->recentlyPaid()
            ->with('spend')
            ->orderByDesc('paid_on')
            ->get();
    }

    protected function card(): Card
    {
        /** @var Card $card */
        $card = $this->getOwnerRecord();

        return $card;
    }

    protected function paymentsRepeatableEntry(string $name, Closure $state, string $placeholder): RepeatableEntry
    {
        return RepeatableEntry::make($name)
            ->hiddenLabel()
            ->contained()
            ->placeholder($placeholder)
            ->state($state)
            ->table([
                RepeatableEntry\TableColumn::make('Spend'),
                RepeatableEntry\TableColumn::make('Amount'),
                RepeatableEntry\TableColumn::make('Date'),
                RepeatableEntry\TableColumn::make('Status'),
            ])
            ->schema($this->paymentInfolistComponents());
    }

    /**
     * @return array<int, TextEntry>
     */
    protected function paymentInfolistComponents(): array
    {
        return [
            TextEntry::make('spend.name')
                ->label('Spend')
                ->placeholder('—'),
            TextEntry::make('amount')
                ->money(fn (Payment $record): string => $record->currency->value),
            TextEntry::make('paid_on')
                ->label('Date')
                ->date()
                ->placeholder('—'),
            TextEntry::make('is_paid')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (bool $state): string => $state ? 'Paid' : 'Upcoming')
                ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
        ];
    }
}
