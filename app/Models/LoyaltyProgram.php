<?php

namespace App\Models;

use App\Enums\LoyaltyKind;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use GetsDumped, HasFactory;

    protected $fillable = [
        'kind',
        'name',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'kind' => LoyaltyKind::class,
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(LoyaltyMembership::class);
    }

    public function transferRoutes(): HasMany
    {
        return $this->hasMany(TransferRoute::class);
    }

    public function perks(): HasMany
    {
        return $this->hasMany(BookingPerk::class);
    }
}
