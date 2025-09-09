<?php

namespace App\Models;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRedemption extends Model
{
    use HasFactory, GetsDumped;

    protected $fillable = [
        'name','activity_id','type',
        'subtype','paid_on',
        'points_program','points_spent',
        'money_spent','cash_value'
    ];

    protected $casts = [
        'type' => SpendType::class,
        'subtype' => SpendSubtype::class,
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
