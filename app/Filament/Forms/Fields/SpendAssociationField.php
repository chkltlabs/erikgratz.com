<?php

namespace App\Filament\Forms\Fields;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type as MorphType;

class SpendAssociationField
{
    public static function make(string $name = 'spend', bool $required = false): MorphToSelect
    {
        $field = MorphToSelect::make($name)
            ->label('Associate To')
            ->types([
                MorphType::make(Spend::class)
                    ->titleAttribute('name')
                    ->getSearchResultsUsing(fn (string $search): array => Spend::query()
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('activity', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Spend $record) => [
                            $record->id => $record->display_name,
                        ])
                        ->toArray()
                    )
                    ->getOptionsUsing(fn (): array => Spend::query()
                        ->with('activity')
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn (Spend $record) => [
                            $record->id => $record->display_name,
                        ])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = Spend::find($value);
                        return $record?->display_name;
                    }),
                MorphType::make(PeriodicSpend::class)
                    ->titleAttribute('name')
                    ->getSearchResultsUsing(fn (string $search): array => PeriodicSpend::query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (PeriodicSpend $record) => [
                            $record->id => $record->display_name,
                        ])
                        ->toArray()
                    )
                    ->getOptionsUsing(fn (): array => PeriodicSpend::query()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn (PeriodicSpend $record) => [
                            $record->id => $record->display_name,
                        ])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = PeriodicSpend::find($value);
                        return $record?->display_name;
                    }),
            ])
            ->searchable();

        if ($required) {
            $field->required();
        }

        return $field;
    }
}
