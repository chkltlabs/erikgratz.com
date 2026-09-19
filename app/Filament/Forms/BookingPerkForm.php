<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use App\Enums\BenefitAppliesTo;
use App\Enums\BookingChannel;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BookingPerkForm
{
    /**
     * @return list<Component>
     */
    public static function components(bool $includeMinTier = false): array
    {
        $fields = [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->maxLength(2048),
            TextInput::make('decision_value')
                ->label('Decision value')
                ->numeric()
                ->prefix('$')
                ->default(0)
                ->required(),
            Select::make('applies_to')
                ->options(BenefitAppliesTo::asSelectArray())
                ->default(BenefitAppliesTo::Any)
                ->required(),
            Select::make('channel')
                ->options(BookingChannel::asSelectArray())
                ->nullable(),
            Toggle::make('award_only')
                ->default(false),
        ];

        if ($includeMinTier) {
            $fields[] = TextInput::make('min_tier')
                ->maxLength(64);
        }

        return $fields;
    }
}
