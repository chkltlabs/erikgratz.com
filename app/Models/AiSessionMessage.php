<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSessionMessage extends Model
{
    protected $fillable = [
        'session_id',
        'role',
        'content',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'role' => AiMessageRole::class,
        'metadata' => 'array',
        'sequence' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiSession::class, 'session_id');
    }
}
