<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\AiChatSource;
use App\Enums\AiImportStatus;
use App\Filament\Resources\AiImportedConversationResource\Pages\ListAiImportedConversations;
use App\Filament\Resources\AiImportedConversationResource\Pages\ViewAiImportedConversation;
use App\Jobs\ProcessImportedConversation;
use App\Models\AiImportedConversation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AiImportedConversationResource extends Resource
{
    protected static ?string $model = AiImportedConversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'AI';

    protected static ?string $navigationLabel = 'Imported chats';

    protected static ?string $modelLabel = 'Imported chat';

    protected static ?string $pluralModelLabel = 'Imported chats';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conversation')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('source'),
                        TextEntry::make('model')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('imported_at')->dateTime(),
                        TextEntry::make('conversation_at')
                            ->label('Conversation time')
                            ->state(fn (AiImportedConversation $record) => $record->messages()->max('occurred_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('external_id')->placeholder('—'),
                        TextEntry::make('error_message')
                            ->columnSpanFull()
                            ->visible(fn (AiImportedConversation $record): bool => filled($record->error_message)),
                        TextEntry::make('messages_count')
                            ->label('Messages')
                            ->state(fn (AiImportedConversation $record): int => $record->messages()->count()),
                        TextEntry::make('chunks_count')
                            ->label('RAG chunks')
                            ->state(fn (AiImportedConversation $record): int => $record->documents()
                                ->withCount('chunks')
                                ->get()
                                ->sum('chunks_count')),
                    ]),
                Section::make('Transcript')
                    ->schema([
                        TextEntry::make('transcript')
                            ->label('')
                            ->state(function (AiImportedConversation $record): string {
                                return $record->messages
                                    ->map(function ($message): string {
                                        return '**'.strtoupper($message->role->value)."**\n\n".$message->content;
                                    })
                                    ->implode("\n\n---\n\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withMax('messages', 'occurred_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(60)->tooltip(fn ($state): ?string => is_string($state) && mb_strlen($state) > 60 ? $state : null),
                TextColumn::make('source')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof AiImportStatus ? $state->value : $state) {
                        AiImportStatus::Ready => 'success',
                        AiImportStatus::Failed => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('model')->toggleable(),
                TextColumn::make('messages_max_occurred_at')
                    ->label('Conversation')
                    ->dateTime()
                    ->sortable()
                    ->placeholder(fn (AiImportedConversation $record): string => $record->imported_at?->toDayDateTimeString() ?? '—'),
                TextColumn::make('imported_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Msgs'),
            ])
            ->defaultSort('messages_max_occurred_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->options(AiChatSource::asSelectArray()),
                SelectFilter::make('status')
                    ->options(AiImportStatus::asSelectArray()),
                Filter::make('messages_count')
                    ->label('Msgs')
                    ->form([
                        TextInput::make('min')
                            ->label('Min msgs')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('max')
                            ->label('Max msgs')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['min'] ?? null),
                                fn (Builder $query): Builder => $query->has('messages', '>=', (int) $data['min']),
                            )
                            ->when(
                                filled($data['max'] ?? null),
                                fn (Builder $query): Builder => $query->has('messages', '<=', (int) $data['max']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['min'] ?? null)) {
                            $indicators[] = 'Msgs ≥ '.(int) $data['min'];
                        }

                        if (filled($data['max'] ?? null)) {
                            $indicators[] = 'Msgs ≤ '.(int) $data['max'];
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reindex')
                    ->label('Re-index')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->action(function (AiImportedConversation $record): void {
                        ProcessImportedConversation::dispatch($record->id);
                        Notification::make()
                            ->title('Re-index queued')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiImportedConversations::route('/'),
            'view' => ViewAiImportedConversation::route('/{record}'),
        ];
    }
}
