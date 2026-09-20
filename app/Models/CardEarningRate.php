<?php

namespace App\Models;

use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardEarningRate extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'card_id',
        'category',
        'channel',
        'vendor',
        'multiplier',
    ];

    protected function casts(): array
    {
        return [
            'category' => BookingCategory::class,
            'channel' => BookingChannel::class,
            'multiplier' => 'float',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    protected function vendor(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? $value : null,
        );
    }
}
