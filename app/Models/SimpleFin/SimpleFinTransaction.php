<?php

namespace App\Models\SimpleFin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'posted' => 'datetime',
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
        'is_pending' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SimpleFinAccount::class, 'simple_fin_account_id');
    }
}
