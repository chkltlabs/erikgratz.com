<?php

namespace App\Filament\Resources\PromoSourceItemResource\Pages;

use App\Filament\Resources\PromoSourceItemResource;
use App\Jobs\IngestTravelPromos;
use App\Services\TravelWallet\PromoIngestor;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;

class ListPromoSourceItems extends ListRecords
{
    protected static string $resource = PromoSourceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetch')
                ->label('Fetch feeds')
                ->successNotificationTitle('Promo ingest queued')
                ->action(function (): void {
                    IngestTravelPromos::dispatch();
                }),
            Action::make('paste')
                ->label('Paste promo')
                ->form([
                    Textarea::make('text')
                        ->required()
                        ->rows(8)
                        ->label('Email or page text'),
                ])
                ->action(function (array $data, Action $action): void {
                    $count = app(PromoIngestor::class)->ingestPlaintext((string) $data['text']);
                    $action->successNotificationTitle("Queued {$count} extracted item(s) for review");
                })
                ->after(function (): void {
                    $this->resetTable();
                }),
        ];
    }
}
