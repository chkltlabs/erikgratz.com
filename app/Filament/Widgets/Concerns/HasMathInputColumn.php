<?php

namespace App\Filament\Widgets\Concerns;

use App\Rules\ValidMathExpression;
use App\Support\MathExpression;
use Filament\Tables\Columns\TextInputColumn;

trait HasMathInputColumn
{
    private function mathInputColumn(string $field, bool $asInteger = false): TextInputColumn
    {
        return TextInputColumn::make($field)
            ->rules([new ValidMathExpression])
            ->updateStateUsing(function ($record, $state) use ($field, $asInteger) {
                $resolved = MathExpression::resolve($state);
                $value = $asInteger ? (int) round($resolved) : $resolved;
                $record->update([$field => $value]);

                return $value;
            });
    }
}
