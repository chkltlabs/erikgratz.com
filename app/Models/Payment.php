<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['spend_id', 'amount', 'is_paid', 'paid_on', 'card_id'];

    protected $casts = ['paid_on' => 'date', 'is_paid' => 'bool'];

    public function spend()
    {
        return $this->belongsTo(Spend::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
