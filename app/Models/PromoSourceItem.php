<?php

namespace App\Models;

use App\Enums\PromoStatus;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoSourceItem extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'source',
        'external_id',
        'title',
        'facts',
        'status',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'status' => PromoStatus::class,
            'fetched_at' => 'datetime',
        ];
    }

    public function transferBonuses(): HasMany
    {
        return $this->hasMany(TransferBonus::class);
    }

    public function earningPromotions(): HasMany
    {
        return $this->hasMany(EarningPromotion::class);
    }
}
