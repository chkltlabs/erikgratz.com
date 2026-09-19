<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiCorpus;
use App\Enums\AiDocumentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiDocument extends Model
{
    protected $fillable = [
        'corpus',
        'kind',
        'title',
        'imported_conversation_id',
        'metadata',
    ];

    protected $casts = [
        'corpus' => AiCorpus::class,
        'kind' => AiDocumentKind::class,
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiImportedConversation::class, 'imported_conversation_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiChunk::class, 'document_id')->orderBy('position');
    }
}
