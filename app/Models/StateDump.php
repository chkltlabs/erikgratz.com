<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateDump extends Model
{
    //Dumps are not meant to be restored to their database tables
    // rather, dumps are meant to record balance and planning state over time
    // so that more sophisticated graphs and data may be collected and displayed

    use HasFactory;

    protected $fillable = ['data'];

    protected $casts = ['data' => 'array'];

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
            $data[$class] = self::getDump($class);
        }
        return self::create(['data' => $data]);
    }

    public static function getDump(string $class): array
    {
        $dump = $class::all();
        $dump->each->toArray();
        return $dump->toArray();
    }
}
