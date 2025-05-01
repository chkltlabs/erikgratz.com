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

    public function __invoke(Builder $Aquery): int|float
    {
        $Bquery = $Aquery->clone();
        $Cquery = $Aquery->clone();
        $Dquery = $Aquery->clone();

        return $Aquery->sum('balance')
            + $Bquery->sum('pending')
            - $Cquery->sum('interest_free_balance')
            + $Dquery->sum('interest_free_balance_payment');

    }
}
