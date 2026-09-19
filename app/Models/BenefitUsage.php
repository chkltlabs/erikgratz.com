<?php

namespace App\Models;

use App\Enums\BenefitUsageSource;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitUsage extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'card_benefit_id',
        'used_on',
        'amount',
        'quantity',
        'source',
        'activity_id',
        'payment_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'used_on' => 'date',
            'amount' => 'float',
            'source' => BenefitUsageSource::class,
        ];
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(CardBenefit::class, 'card_benefit_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
