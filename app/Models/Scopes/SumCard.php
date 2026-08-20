<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

class SumCard
{
    public function __construct()
    {
        //
    }

    public function __invoke(Builder $query): int|float
    {
        return (float) $query->clone()
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(balance + pending - interest_free_balance + interest_free_balance_payment), 0) as aggregate'
            )
            ->value('aggregate');
    }
}
