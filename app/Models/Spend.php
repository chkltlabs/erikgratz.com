<?php

namespace App\Models;

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

    //    protected $casts = [
    //        'type' => '',
    //        'subtype' => ''
    //    ];

}
