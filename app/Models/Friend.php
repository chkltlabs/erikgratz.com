<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'region', 'color'];

    protected $casts = ['region' => 'array'];

    public function recommendations()
    {
        return $this->hasMany(FriendPlaceRecommendation::class, 'friend_id', 'id');
    }
}
