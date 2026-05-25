<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\CurrencyCode;
use App\Models\SimpleFin\SimpleFinAccount;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\GetsDumped;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Cache;

class Account extends Model
{
    use BelongsToUser, GetsDumped, HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Account $account): void {
            if ($account->wasChanged('currency')) {
                Cache::forget('stateDumps');
            }
        });
    }

    protected $fillable = ['user_id', 'type', 'balance', 'currency'];

    protected $casts = [
        'type' => AccountType::class,
        'currency' => CurrencyCode::class,
    ];


    public function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user->name.' '.$this->type,
        );
    }

    public function balanceInUsd(): float
    {
        return app(ExchangeRateService::class)->convertToUsd(
            (float) $this->balance,
            $this->currency,
        );
    }

    /**
     * @param  Builder<Account>|QueryBuilder|null  $query
     */
    public static function sumBalanceInUsd(Builder|QueryBuilder|null $query = null): float
    {
        return app(ExchangeRateService::class)->sumBalancesInUsd(static::resolveQuery($query));
    }

    /**
     * @param  Builder<Account>|QueryBuilder|null  $query
     * @return Builder<Account>
     */
    protected static function resolveQuery(Builder|QueryBuilder|null $query = null): Builder
    {
        if ($query === null) {
            return static::query();
        }

        if ($query instanceof QueryBuilder) {
            return static::query()->setQuery($query);
        }

        return $query;
    }

    public function simpleFinAccount(): MorphOne
    {
        return $this->morphOne(SimpleFinAccount::class, 'associated');
    }
}
