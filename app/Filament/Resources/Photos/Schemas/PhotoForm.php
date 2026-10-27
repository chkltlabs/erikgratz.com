<?php

namespace App\Filament\Resources\Photos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PhotoForm
{
    /**
     * Kilobytes. Matches Livewire's default temporary upload cap (12MB).
     */
    public const MAX_UPLOAD_KILOBYTES = 12288;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                FileUpload::make('path')
                    ->disk('public')
                    ->directory('form-attachments')
                    ->visibility('public')
                    ->maxSize(self::MAX_UPLOAD_KILOBYTES)
                    ->hiddenOn('edit')
                    ->reactive(),
                TextInput::make('url')
                    ->disabled()
                    ->dehydrated()
                    ->label('Image URL')
                    ->placeholder('URL will be generated after upload'),

                Textarea::make('description')
                    ->columnSpanFull(),
                TagsInput::make('tags'),
            ]);
    }
}
