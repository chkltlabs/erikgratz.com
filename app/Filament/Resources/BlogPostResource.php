<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Filament\Resources\BlogPostResource\RelationManagers;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('user_id')
                    ->required(),
                Forms\Components\DateTimePicker::make('posted'),
                Forms\Components\DateTimePicker::make('edited'),
                Forms\Components\TextInput::make('subtitle')
                    ->maxLength(191),
                Forms\Components\Textarea::make('body'),
                Forms\Components\TextInput::make('imageUrl')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Toggle::make('is_public')
                    ->required(),
                Forms\Components\TagsInput::make('tags'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('user_id'),
                Tables\Columns\TextColumn::make('posted')->dateTime(),
                Tables\Columns\TextColumn::make('edited')->dateTime(),
                Tables\Columns\TextColumn::make('subtitle'),
                Tables\Columns\TextColumn::make('body'),
                Tables\Columns\TextColumn::make('imageUrl'),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
                Tables\Columns\TagsColumn::make('tags'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }    
}
