<?php

namespace App\Filament\Resources\Photos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
//                TextInput::make('url')
//                    ->url()
//                    ->required(),
                FileUpload::make('path')
                    ->disk('public')
                    ->directory('form-attachments')
                    ->visibility('public')
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
