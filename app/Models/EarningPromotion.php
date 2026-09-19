<?php

namespace App\Models;

use App\Enums\PointsProgram;
use App\Enums\PromoStatus;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EarningPromotion extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'card_id',
        'points_program',
        'promo_source_item_id',
        'category',
        'merchant',
        'multiplier',
        'starts_at',
        'ends_at',
        'status',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'points_program' => PointsProgram::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => PromoStatus::class,
            'multiplier' => 'float',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(PromoSourceItem::class, 'promo_source_item_id');
    }

    public function isLive(?Carbon $asOf = null): bool
    {
        $asOf = $asOf ?? now();

        if (! $this->status->is(PromoStatus::Active)) {
            return false;
        }

        if ($this->starts_at && $asOf->lt($this->starts_at->startOfDay())) {
            return false;
        }

        if ($this->ends_at && $asOf->gt($this->ends_at->endOfDay())) {
            return false;
        }

        return true;
    }

    public function scopeLive(Builder $query, ?Carbon $asOf = null): Builder
    {
        $asOf = $asOf ?? now();

        return $query
            ->where('status', PromoStatus::Active)
            ->where(function (Builder $inner) use ($asOf): void {
                $inner->whereNull('starts_at')->orWhereDate('starts_at', '<=', $asOf);
            })
            ->where(function (Builder $inner) use ($asOf): void {
                $inner->whereNull('ends_at')->orWhereDate('ends_at', '>=', $asOf);
            });
    }
}
