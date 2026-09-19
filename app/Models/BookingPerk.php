<?php

namespace App\Models;

use App\Enums\BenefitAppliesTo;
use App\Enums\BookingChannel;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class BookingPerk extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'loyalty_program_id',
        'loyalty_membership_id',
        'card_id',
        'card_benefit_id',
        'min_tier',
        'name',
        'description',
        'decision_value',
        'applies_to',
        'channel',
        'award_only',
    ];

    protected function casts(): array
    {
        return [
            'decision_value' => 'float',
            'applies_to' => BenefitAppliesTo::class,
            'channel' => BookingChannel::class,
            'award_only' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BookingPerk $perk): void {
            $owners = collect([
                $perk->loyalty_program_id,
                $perk->loyalty_membership_id,
                $perk->card_id,
                $perk->card_benefit_id,
            ])->filter(fn (mixed $id): bool => $id !== null)->count();

            if ($owners !== 1) {
                throw new InvalidArgumentException('A booking perk must belong to exactly one owner.');
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMembership::class, 'loyalty_membership_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(CardBenefit::class, 'card_benefit_id');
    }

    public function appliesToCategory(string $category): bool
    {
        if ($this->applies_to->is(BenefitAppliesTo::Other)) {
            return false;
        }

        if ($this->applies_to->is(BenefitAppliesTo::Any)) {
            return true;
        }

        return $this->applies_to->value === $category;
    }
}
