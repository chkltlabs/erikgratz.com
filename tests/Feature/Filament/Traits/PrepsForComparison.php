<?php

namespace Tests\Feature\Filament\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Pipeline;

trait PrepsForComparison
{
    private static function prepModelForComparison(Model $model)
    {
        return Pipeline::send($model)
            ->through([
                // apply unsets
                function (Model $model, Closure $next) {
                    if (! empty(static::$unsetAttributesBeforeCompare)) {
                        foreach (static::$unsetAttributesBeforeCompare as $unsetAttr) {
                            unset($model->$unsetAttr);
                        }
                    }

                    return $next($model);
                },
                // always unset timestamps and keys
                function (Model $model, Closure $next) {
                    $timestamps = array_filter(
                        array_keys($model->getAttributes()),
                        fn ($col) => str_contains($col, 'date')
                            || str_contains($col, '_at'));

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

        self::assertEquals(
            $new->withoutTimestamps(fn () => self::prepModelForComparison($new)->toArray()),
            $model->withoutTimestamps(fn () => self::prepModelForComparison($model)->toArray())
        );
    }

    public static array $unsetAttributesBeforeCompare = [];
}
