<?php

namespace App\Models;

use App\Enums\SpendSubtype;
use App\Enums\SpendType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spend extends Model
{
    use HasFactory;

    protected $fillable = [
        'spend_for',
        'spend_at',
        'name',
        'amount',
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

}
