<?php

namespace App\Models\SimpleFin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimpleFinOrganization extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'domain',
        'url',
        'sfin_url',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(SimpleFinAccount::class);
    }
}
