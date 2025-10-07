<?php

namespace App\Models\Collections;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StateDumpCollection extends Collection
{
    public function getStatArrayForModel(Model $model, string $col, ?Carbon $since = null): array
    {
        $rtn = [];
        foreach ($this->items as $stateDump) {
            if (! is_null($since) && $stateDump->created_at->lt($since)) {
                continue;
            }
            //            $rtn[] = $stateDump->data[$model::class][$col] ?? 0;
            $rtn[$stateDump->created_at->timestamp] = $stateDump->getStatForModel($model, $col);
        }

        return $rtn;
    }

    public function sumStatArraysForAllModels(?Model $model, string $col, ?Carbon $since = null): array
    {
        if (is_null($model)) {
            return [];
        }
        $models = $model::class::all();
        $rtn = [];
        foreach ($models as $specificModel) {
            $modelRtn = $this->getStatArrayForModel($specificModel, $col, $since);
            $rtn = self::combineArrs($rtn, $modelRtn);
        }

        return $rtn;
    }

    public static function combineArrs(array $one = [], array $two = []): array
    {
        foreach ($two as $timestamp => $stat) {
            $one[$timestamp] = ($one[$timestamp] ?? 0) + $stat;
        }

        return $one;
    }

    public static function setArrayNegative(array $array): array
    {
        //        array_walk($array, fn ($val) => $val * -1);
        $array = collect($array)->map(fn ($item) => $item * -1)->toArray();

        return $array;
    }
}
