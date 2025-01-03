<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StateDump extends Model
{
    //Dumps are not meant to be restored to their database tables
    // rather, dumps are meant to record balance and planning state over time
    // so that more sophisticated graphs and data may be collected and displayed

    use HasFactory;

    protected $fillable = ['data'];

    protected $casts = ['data' => 'array'];

    // be sure each class in this array implements the GetsDumped trait
    public static $dumpables = [
        Account::class,
        Activity::class,
        Card::class,
        Payment::class,
        Spend::class,
    ];

    public static function dump(): self
    {
        $data = [];
        foreach (self::$dumpables as $class) {
            $data[$class] = $class::getDump();
        }
        return self::create(['data' => $data]);
    }

    const SHOULD_DUMP = 'data_will_dump_tonight';
    public static function setShouldDumpFlag(): void
    {
        Cache::put(self::SHOULD_DUMP, true);
    }

    public static function checkShouldDump(): void
    {
        if (Cache::has(self::SHOULD_DUMP)) {
            Cache::forget(self::SHOULD_DUMP);
            self::dump();
        }
    }
}
