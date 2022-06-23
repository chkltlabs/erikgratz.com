<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendPlaceRecommendation extends Model
{
    use HasFactory;

    protected $fillable = ['friend_id', 'review_title', 'review_body', 'region'];

    protected $casts = ['region' => 'array'];

    public function friend()
    {
        return $this->belongsTo(Friend::class);
    }
}
