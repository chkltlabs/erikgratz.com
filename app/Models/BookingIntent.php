<?php

namespace App\Models;

use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingIntent extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'category',
        'activity_id',
        'vendor',
        'destination',
        'cash_price',
        'cabin',
        'award_quotes',
        'ranking',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'category' => BookingCategory::class,
            'cabin' => BookingCabin::class,
            'award_quotes' => 'array',
            'ranking' => 'array',
            'cash_price' => 'float',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
