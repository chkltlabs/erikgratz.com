<?php

namespace App\Models;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spend extends Model
{
    use HasFactory, GetsDumped;

    protected $fillable = [
        'name',
        'is_income',
        'type',
        'subtype',
    ];

    protected $casts = [
        'type' => SpendType::class,
        'subtype' => SpendSubtype::class,
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->sum('amount')
        );
    }
}
