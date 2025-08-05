<?php

namespace App\Models\Traits;

use App\Models\Card;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasPayments
{
    protected static function bootHasPayments()
    {
        static::deleting(function ($model) {
            $model->payments()->delete();
        });
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'spend');
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->sum('amount')
        );
    }

    public static function paymentFilamentFormComponent(): Repeater
    {
        return Repeater::make('payments')
            ->columns(4)
            ->relationship()
            ->schema([
                TextInput::make('amount')
                    ->prefix('$')
                    ->numeric()
                    ->required(),
                Toggle::make('is_paid'),
                DatePicker::make('paid_on')->nullable(),
                Select::make('card_id')
                    ->label('Card')
                    ->options(Card::all()->pluck('name', 'id'))
                    ->nullable(),
            ])->defaultItems(0)->addActionLabel('Add Payment');
    }
}
