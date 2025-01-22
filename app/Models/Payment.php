<?php

namespace App\Models;

use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory, GetsDumped;

    protected $fillable = ['spend_id', 'spend_type', 'amount', 'is_paid', 'paid_on', 'card_id'];

    protected $casts = ['paid_on' => 'date', 'is_paid' => 'bool'];

    public function spend(): MorphTo
    {
        return $this->morphTo();
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
