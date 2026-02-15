<?php

namespace App\Models\SimpleFin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SimpleFinTransaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'simple_fin_account_id',
        'posted',
        'amount',
        'description',
        'payee',
        'memo',
        'transacted_at',
        'is_pending',
        'is_confirmed',
        'spend_type',
        'spend_id',
    ];

    protected $casts = [
        'posted' => 'datetime',
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
        'is_pending' => 'boolean',
        'is_confirmed' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SimpleFinAccount::class, 'simple_fin_account_id');
    }

    public function spend(): MorphTo
    {
        return $this->morphTo(__FUNCTION__);
    }
}
