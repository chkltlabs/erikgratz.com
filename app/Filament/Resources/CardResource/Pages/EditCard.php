<?php

namespace App\Filament\Resources\CardResource\Pages;

use App\Enums\CardProduct;
use App\Filament\Resources\CardResource;
use App\Filament\Resources\CardResource\Widgets\CardFeeRoi;
use App\Models\Card;
use App\Services\TravelWallet\CardProductCatalog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCard extends EditRecord
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyBenefitSet')
                ->label('Apply benefit set')
                ->icon(Heroicon::OutlinedGift)
                ->schema([
                    Select::make('benefit_set')
                        ->label('Benefit set')
                        ->options(fn (): array => app(CardProductCatalog::class)->options())
                        ->default(function (): ?string {
                            /** @var Card $card */
                            $card = $this->getRecord();

                            return CardProduct::fromCardName((string) $card->name)?->value;
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Card $card */
                    $card = $this->getRecord();

                    app(CardProductCatalog::class)->apply(
                        $card,
                        CardProduct::fromValue($data['benefit_set']),
                    );

                    Notification::make()
                        ->title('Benefit set applied')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CardFeeRoi::class,
        ];
    }
}
