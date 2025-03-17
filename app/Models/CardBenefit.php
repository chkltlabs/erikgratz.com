<?php

namespace App\Models;

use App\Enums\ResetPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardBenefit extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'card_id', 'benefit',
        'description','is_useable',
        'is_used','value','reset_period'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'is_useable' => 'boolean',
        'reset_period' => ResetPeriod::class
    ];
}
