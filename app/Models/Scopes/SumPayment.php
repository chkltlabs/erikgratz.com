<?php

namespace App\Models\Scopes;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SumPayment
{
    public function __construct(

    ) {
        //
    }

    public function __invoke(Builder $query): int|float
    {
        $otherQuery = $query->clone();

        return $query->whereMorphRelation(
            'spend',
            [Spend::class, PeriodicSpend::class],
            'is_income', '=', false)->sum('amount')
            - $otherQuery->whereMorphRelation(
                'spend',
                [Spend::class, PeriodicSpend::class],
                'is_income', '=', true)->sum('amount')
            ;

    }
}
