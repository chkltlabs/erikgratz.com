<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiChatSource;
use App\Enums\AiImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiImportedConversation extends Model
{
    public const TITLE_MAX_LENGTH = 2048;

    protected $fillable = [
        'source',
        'title',
        'model',
        'external_id',
        'import_key',
        'revision',
        'imported_at',
        'raw_payload',
        'status',
        'error_message',
        'user_id',
    ];

    protected $casts = [
        'source' => AiChatSource::class,
        'status' => AiImportStatus::class,
        'imported_at' => 'datetime',
        'revision' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiImportedMessage::class, 'conversation_id')->orderBy('sequence');
    }

    public function document(): HasOne
    {
        return $this->hasOne(AiDocument::class, 'imported_conversation_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AiDocument::class, 'imported_conversation_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (AiImportedConversation $conversation): void {
            $chunkIds = AiChunk::query()
                ->whereHas('document', fn ($query) => $query->where('imported_conversation_id', $conversation->id))
                ->pluck('id');

            if ($chunkIds->isNotEmpty()) {
                AiChunkEmbedding::query()->whereIn('chunk_id', $chunkIds)->delete();
            }
        });
    }
}
