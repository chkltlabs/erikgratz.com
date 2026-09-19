<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiImportedConversationResource\Pages;

use App\Filament\Resources\AiImportedConversationResource;
use App\Jobs\ProcessImportedConversation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewAiImportedConversation extends ViewRecord
{
    protected static string $resource = AiImportedConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reindex')
                ->label('Re-index')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    ProcessImportedConversation::dispatch($this->record->id);
                    Notification::make()
                        ->title('Re-index queued')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
