<?php

namespace App\Models;

use App\Enums\PointsProgram;
use App\Enums\PromoStatus;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TransferRoute extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'from_program',
        'loyalty_program_id',
        'base_ratio',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'from_program' => PointsProgram::class,
            'base_ratio' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(TransferBonus::class);
    }

    public function activeBonus(?Carbon $asOf = null): ?TransferBonus
    {
        $asOf = $asOf ?? now();

        return $this->bonuses
            ->first(function (TransferBonus $bonus) use ($asOf): bool {
                if (! $bonus->status->is(PromoStatus::Active)) {
                    return false;
                }

                if ($bonus->starts_at && $asOf->lt($bonus->starts_at->startOfDay())) {
                    return false;
                }

                if ($bonus->ends_at && $asOf->gt($bonus->ends_at->endOfDay())) {
                    return false;
                }

                return true;
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
