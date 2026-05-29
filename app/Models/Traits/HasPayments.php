<?php

namespace App\Models\Traits;

use App\Enums\CurrencyCode;
use App\Models\Card;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
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
            get: fn () => round(
                $this->payments->sum(fn (Payment $payment): float => $payment->amountInUsd()),
                2,
            ),
        );
    }

    public static function paymentFilamentFormComponent(): Repeater
    {
        return Repeater::make('payments')
            ->columns(4)
            ->relationship()
            ->schema([
                Group::make([
                    Select::make('currency')
                        ->options(CurrencyCode::options())
                        ->default(CurrencyCode::USD->value)
                        ->required()
                        ->live(),
                    TextInput::make('amount')
                        ->prefix(fn ($get): string => (string) ($get('currency') ?? CurrencyCode::USD->value))
                        ->numeric()
                        ->required(),
                ])->columnSpan(2),
                Group::make([
                    Toggle::make('is_paid'),
                    DatePicker::make('paid_on')->nullable(),
                    Select::make('card_id')
                        ->label('Card')
                        ->options(Card::all()->pluck('name', 'id'))
                        ->nullable(),
                ])->columnSpan(2),
            ])->defaultItems(0)->addActionLabel('Add Payment');
    }
}
