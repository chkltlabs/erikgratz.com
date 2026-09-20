<?php

namespace App\Filament\Concerns;

use App\Rules\ValidMathExpression;
use App\Support\MathExpression;
use Filament\Tables\Columns\TextInputColumn;

trait HasMathInputColumn
{
    private static function mathInputColumn(string $field, bool $asInteger = false): TextInputColumn
    {
        return TextInputColumn::make($field)
            ->width('6rem')
            ->extraAttributes([
                'style' => 'min-width: 6rem !important;',
            ])
            ->rules([new ValidMathExpression])
            ->updateStateUsing(function ($record, $state) use ($field, $asInteger) {
                $resolved = MathExpression::resolve($state);
                $value = $asInteger ? (int) round($resolved) : $resolved;
                $record->update([$field => $value]);

                return $value;
            });
    }
}
