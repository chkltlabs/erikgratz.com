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

    public function capturedAmount(): float
    {
        if ($this->amount !== null) {
            return (float) $this->amount;
        }

        $quantity = (int) ($this->quantity ?? 0);

        return $quantity > 0 ? (float) ($this->benefit?->value ?? 0) : 0.0;
    }

    public static function getDump(): array
    {
        return static::query()
            ->with('benefit:id,card_id,value')
            ->get()
            ->map(function (self $usage): array {
                $row = $usage->toArray();
                unset($row['benefit']);
                $row['card_id'] = $usage->benefit?->card_id;
                $row['captured'] = $usage->capturedAmount();

                return $row;
            })
            ->all();
    }
}
