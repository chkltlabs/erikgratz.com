<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(191),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(191),
                TextInput::make('name')
                    ->required()
                    ->maxLength(191),
                TextInput::make('imageUrl')
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('imageUrl')
                    ->searchable(),

                IconColumn::make('simple_fin_url')
                    ->label('SimpleFIN')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->tooltip('SimpleFIN URL not set')
                    ->visible(fn (?User $record): bool => (bool) blank($record?->simple_fin_url)),

                TextColumn::make('simple_fin_url')
                    ->label('SimpleFIN')
                    ->formatStateUsing(fn (): string => 'Copy')
                    ->copyable()
                    ->copyableState(fn (?User $record): string => (string) ($record?->simple_fin_url ?? ''))
                    ->copyMessage('SimpleFIN URL copied')
                    ->tooltip(fn (?User $record): string => (string) ($record?->simple_fin_url ?? ''))
                    ->visible(fn (?User $record): bool => filled($record?->simple_fin_url)),

                Tables\Columns\TextInputColumn::make('simple_fin_token')
                    ->label('SimpleFIN Token')
                    ->state(fn () => null)
                    ->updateStateUsing(fn () => null)
                    ->afterStateUpdated(function ($state, User $record) {
                        if (empty($state)) {
                            return;
                        }

                        $claimUrl = base64_decode($state);
                        if ($claimUrl === false) {
                            \Filament\Notifications\Notification::make()
                                ->title('Invalid Token')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $response = \Illuminate\Support\Facades\Http::post($claimUrl);
                            if ($response->successful()) {
                                $record->update([
                                    'simple_fin_url' => $response->body(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('SimpleFIN URL updated successfully')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to exchange token')
                                    ->body($response->body())
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('An error occurred')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
