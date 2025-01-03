<?php

namespace App\Models\Traits;

use App\Models\StateDump;

trait GetsDumped
{
    public static function bootGetsDumped()
    {
        static::saved(function ($model) {
            StateDump::setShouldDumpFlag();
        });
    }

    public static function getDump(): array
    {
        $dump = static::all();
        $dump->each->toArray();
        return $dump->toArray();
    }
}
