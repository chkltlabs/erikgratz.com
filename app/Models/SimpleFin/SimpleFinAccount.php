<?php

namespace App\Models\SimpleFin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimpleFinAccount extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'simple_fin_organization_id',
        'name',
        'currency',
        'balance',
        'available_balance',
        'balance_date',
        'associated_id',
        'associated_type',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'balance_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(SimpleFinOrganization::class, 'simple_fin_organization_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SimpleFinTransaction::class);
    }

    public function associated(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
