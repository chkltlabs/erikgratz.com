<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiImportedConversationResource\Pages;

use App\Enums\AiChatSource;
use App\Filament\Resources\AiImportedConversationResource;
use App\Jobs\ProcessImportedConversation;
use App\Services\Ai\FrontierChatImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListAiImportedConversations extends ListRecords
{
    protected static string $resource = AiImportedConversationResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import chat')
                ->schema([
                    Select::make('source')
                        ->label('Source')
                        ->options(AiChatSource::asSelectArray())
                        ->default(AiChatSource::Gemini)
                        ->required()
                        ->helperText('Ignored for Takeout HTML (always stored as Google AI Mode / Takeout).'),
                    TextInput::make('title')
                        ->label('Title override')
                        ->maxLength(2048)
                        ->helperText('Only applied when importing a single conversation.'),
                    Textarea::make('payload')
                        ->label('Paste transcript / JSON / HTML')
                        ->rows(16)
                        ->columnSpanFull(),
                    FileUpload::make('upload')
                        ->label('Or upload file')
                        ->acceptedFileTypes([
                            'application/json',
                            'text/plain',
                            'text/markdown',
                            'text/x-markdown',
                            'text/html',
                            'application/xhtml+xml',
                        ])
                        ->maxSize(20480)
                        ->disk('local')
                        ->directory('ai-imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data, FrontierChatImporter $importer): void {
                    $payload = trim((string) ($data['payload'] ?? ''));
                    $filename = null;
                    $upload = $data['upload'] ?? null;
                    if (is_array($upload)) {
                        $upload = $upload[0] ?? null;
                    }

                    $uploadPath = null;
                    if (is_string($upload) && $upload !== '') {
                        $uploadPath = $upload;
                        $filename = basename($upload);
                        $payload = Storage::disk('local')->get($upload) ?? '';
                    }

                    if (trim($payload) === '') {
                        Notification::make()
                            ->title('Nothing to import')
                            ->body('Paste a transcript or upload a file.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $result = $importer->importAllFromPayload(
                            payload: $payload,
                            userId: Auth::id(),
                            filename: $filename,
                            preferredSource: (string) ($data['source'] ?? AiChatSource::Other),
                            titleOverride: filled($data['title'] ?? null) ? (string) $data['title'] : null,
                        );

                        foreach ($result['conversations'] as $conversation) {
                            ProcessImportedConversation::dispatch($conversation->id);
                        }

                        $count = count($result['conversations']);
                        Notification::make()
                            ->title($count === 1 ? 'Import queued' : "{$count} conversations queued")
                            ->body("Imported {$result['created']} new, updated {$result['updated']} existing. Indexing queued.")
                            ->success()
                            ->send();

                        if ($count === 1) {
                            $this->redirect(AiImportedConversationResource::getUrl('view', [
                                'record' => $result['conversations'][0],
                            ]));

                            return;
                        }

                        $this->redirect(AiImportedConversationResource::getUrl('index'));
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('Import failed')
                            ->body('Import failed. Check the logs for details.')
                            ->danger()
                            ->send();
                    } finally {
                        if (is_string($uploadPath) && $uploadPath !== '') {
                            Storage::disk('local')->delete($uploadPath);
                        }
                    }
                }),
        ];
    }
}
