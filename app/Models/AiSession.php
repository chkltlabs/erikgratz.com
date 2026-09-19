<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiSurface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSession extends Model
{
    protected $fillable = [
        'surface',
        'user_id',
        'ip_hash',
        'public_handle_hash',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'surface' => AiSurface::class,
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiSessionMessage::class, 'session_id')->orderBy('sequence');
    }
}
