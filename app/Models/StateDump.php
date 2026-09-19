<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Models\Collections\StateDumpCollection;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StateDump extends Model
{
    // Dumps are not meant to be restored to their database tables
    // rather, dumps are meant to record balance and planning state over time
    // so that more sophisticated graphs and data may be collected and displayed

    use HasFactory;

    protected $fillable = ['data'];

    protected $casts = ['data' => 'array'];

    public function newCollection(array $models = []): StateDumpCollection
    {
        return new StateDumpCollection($models);
    }

    // be sure each class in this array implements the GetsDumped trait
    public static $dumpables = [
        Account::class,
        Activity::class,
        Card::class,
        Payment::class,
        Spend::class,
        PeriodicSpend::class,
        SimpleFinAccount::class,
    ];

    public static function dump(): self
    {
        $data = [];
        foreach (self::$dumpables as $class) {
            $data[$class] = $class::getDump();
        }

        if (isset($data[Account::class])) {
            $currencies = collect($data[Account::class])
                ->map(fn (array $row): string => self::currencyFromDumpRow($row))
                ->unique()
                ->values()
                ->all();

            $data['exchange_rates'] = [
                'date' => now()->toDateString(),
                'base' => 'USD',
                'multipliers' => app(ExchangeRateService::class)->multipliersForDump($currencies),
            ];
        }

        return self::create(['data' => $data]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected static function currencyFromDumpRow(array $row): string
    {
        $currency = $row['currency'] ?? CurrencyCode::USD->value;

        if ($currency instanceof CurrencyCode) {
            return $currency->value;
        }

        return strtoupper((string) $currency);
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

    public function getStatForModel(Model $model, string $col): int|float|string
    {
        $foundState = collect($this->data[$model::class] ?? [])
            ->first(
                fn ($item) => $item[$model->getKeyName()]
                    === $model->getKey()
            );

        if (is_null($foundState)) {
            return 0;
        }

        if ($model instanceof Account && $col === 'balance') {
            return $this->accountBalanceInUsd($foundState);
        }

        return $foundState[$col];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function accountBalanceInUsd(array $row): float
    {
        $balance = (float) ($row['balance'] ?? 0);
        $currency = self::currencyFromDumpRow($row);
        $multipliers = $this->data['exchange_rates']['multipliers'] ?? [];

        return $balance * ($multipliers[$currency] ?? 1.0);
    }
}
