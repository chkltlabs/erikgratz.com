<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpleFinRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'pattern',
        'spend_type',
        'spend_id',
    ];

    public function spend(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
