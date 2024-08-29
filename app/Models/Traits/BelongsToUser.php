<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, string|int $userID)
    {
        if (is_numeric($userID)) {
            return $query->where('user_id', $userID);
        }
        return $query->whereRelation('user', 'name', 'like', '%'.$userID.'%');
    }
}
