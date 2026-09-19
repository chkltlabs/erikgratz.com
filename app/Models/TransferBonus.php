<?php

namespace App\Models;

use App\Enums\PromoStatus;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferBonus extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'transfer_route_id',
        'promo_source_item_id',
        'bonus_percent',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => PromoStatus::class,
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransferRoute::class, 'transfer_route_id');
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(PromoSourceItem::class, 'promo_source_item_id');
    }
}
