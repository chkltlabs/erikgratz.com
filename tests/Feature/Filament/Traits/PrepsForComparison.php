<?php

namespace Tests\Feature\Filament\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Pipeline;

trait PrepsForComparison
{
    private static array $ignoredGlobalAttributes = ['imageUrl', 'monthly_pay', 'simple_fin_url'];

    private static function prepModelForComparison(Model $model)
    {
        return Pipeline::send($model)
            ->through([
                // apply unsets
                function (Model $model, Closure $next) {
                    $toUnset = array_merge(
                        static::$unsetAttributesBeforeCompare,
                        self::$ignoredGlobalAttributes
                    );
                    foreach ($toUnset as $unsetAttr) {
                        unset($model->$unsetAttr);
                    }

                    return $next($model);
                },
                // always unset timestamps and keys
                function (Model $model, Closure $next) {
                    $timestamps = array_filter(
                        array_keys($model->getAttributes()),
                        fn ($col) => (str_contains($col, 'date')
                            || str_contains($col, '_at')
                            || str_contains($col, '_on'))
                    );

                    foreach ($timestamps as $timestamp) {
                        unset($model->$timestamp);
                    }

                    unset($model->{$model->getKeyName()});

                    return $next($model);
                },
            ])
            ->then(fn (Model $model) => $model);
    }

    private static function assertModelsEqual(Model $model, Model $new): void
    {
        $new->refresh();
        $model->refresh();

        $expected = $new->withoutTimestamps(fn () => self::prepModelForComparison($new)->toArray());
        $actual = $model->withoutTimestamps(fn () => self::prepModelForComparison($model)->toArray());

        foreach ($expected as $key => $value) {
            if (is_numeric($value) && isset($actual[$key]) && is_numeric($actual[$key])) {
                // intentionally left blank, handled by next line
            }
        }

        // Round numeric values to 4 decimal places to handle precision issues
        array_walk_recursive($expected, function (&$item) {
            if (is_numeric($item) && ! is_string($item)) {
                $item = round((float) $item, 4);
            }
        });
        array_walk_recursive($actual, function (&$item) {
            if (is_numeric($item) && ! is_string($item)) {
                $item = round((float) $item, 4);
            }
        });

        self::assertEquals($expected, $actual);
    }

    public static array $unsetAttributesBeforeCompare = [];
}
