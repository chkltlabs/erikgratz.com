<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChunk extends Model
{
    protected $fillable = [
        'document_id',
        'content',
        'position',
        'token_count',
        'embedding_model',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'position' => 'integer',
        'token_count' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'document_id');
    }
}
