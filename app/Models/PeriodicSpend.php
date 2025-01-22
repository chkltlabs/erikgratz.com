<?php

namespace App\Models;

use App\Enums\Period;
use App\Models\Traits\GetsDumped;
use App\Models\Traits\HasPayments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodicSpend extends Model
{
    use HasFactory, GetsDumped, HasPayments;

    protected $fillable = [
        'period', 'name', 'start_date', 'end_date',
        'is_income','type','subtype'
    ];

    protected $casts = [
        'period' => Period::class,
    ];
}
