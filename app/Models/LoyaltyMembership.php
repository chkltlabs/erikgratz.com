<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyMembership extends Model
{
    use BelongsToUser, GetsDumped, HasFactory;

    protected $fillable = [
        'user_id',
        'loyalty_program_id',
        'tier',
        'loyalty_number',
        'conferred_by_card_id',
        'points_balance',
    ];

    public function displayLabel(): string
    {
        $account = trim($this->program?->name.' '.($this->tier ?? ''));
        $member = $this->user?->name;

        if (! filled($member)) {
            return $account;
        }

        return $member.' · '.$account;
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function conferredByCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'conferred_by_card_id');
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(CardBenefit::class);
    }

    public function perks(): HasMany
    {
        return $this->hasMany(BookingPerk::class);
    }

    public function inheritedPerks(): HasMany
    {
        return $this->hasMany(BookingPerk::class, 'loyalty_program_id', 'loyalty_program_id');
    }
}
