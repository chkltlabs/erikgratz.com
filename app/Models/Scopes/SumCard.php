<?php

namespace App\Models\Scopes;

use App\Models\PeriodicSpend;
use App\Models\Spend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SumCard
{
    public function __construct()
    {
        //
    }

    public function __invoke(Builder $query): int|float
    {
        return $query->sum('balance')
            + $query->sum('pending')
            - $query->sum('interest_free_balance')
            + $query->sum('interest_free_balance_payment');

    }
}
