<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiImportedMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'sequence',
        'occurred_at',
    ];

    protected $casts = [
        'role' => AiMessageRole::class,
        'occurred_at' => 'datetime',
        'sequence' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiImportedConversation::class, 'conversation_id');
    }
}
