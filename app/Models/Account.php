<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\GetsDumped;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory, BelongsToUser, GetsDumped;

    protected $fillable = ['user_id', 'type', 'balance'];

    protected $casts = [
        'type' => AccountType::class,
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user->name . ' ' . $this->type,
        );
    }
}
