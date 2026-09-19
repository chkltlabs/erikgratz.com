<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Retrieval\CorpusEmbedder;
use App\Enums\AiCorpus;
use App\Enums\AiDocumentKind;
use App\Enums\AiImportStatus;
use App\Models\AiChunk;
use App\Models\AiChunkEmbedding;
use App\Models\AiDocument;
use App\Models\AiImportedConversation;
use Illuminate\Support\Facades\DB;

class FrontierChatIndexer
{
    public function __construct(
        protected CorpusEmbedder $embedder,
        protected ConversationChunker $chunker,
    ) {}

    public function index(AiImportedConversation $conversation): void
    {
        $conversation->loadMissing('messages');
        $expectedRevision = (int) $conversation->revision;

        $conversation->update([
            'status' => AiImportStatus::Indexing,
            'error_message' => null,
        ]);

        $planned = $this->chunker->chunkMessages($conversation->messages);
        $contents = array_column($planned, 'content');
        $vectors = $this->embedder->embed($contents);
        $model = $this->embedder->spec()->model;

        $embedded = [];
        foreach ($planned as $position => $chunk) {
            $embedded[] = [
                'content' => $chunk['content'],
                'position' => $position,
                'token_count' => $this->estimateTokens($chunk['content']),
                'embedding' => $vectors[$position],
                'embedding_model' => $model,
                'metadata' => $chunk['metadata'],
            ];
        }

        $created = DB::transaction(function () use ($conversation, $expectedRevision, $embedded): array {
            $fresh = AiImportedConversation::query()->lockForUpdate()->find($conversation->id);
            if (! $fresh || (int) $fresh->revision !== $expectedRevision) {
                return [];
            }

            $previousChunkIds = AiChunk::query()
                ->whereHas('document', fn ($query) => $query->where('imported_conversation_id', $fresh->id))
                ->pluck('id');

            $fresh->documents()->delete();

            $document = AiDocument::query()->create([
                'corpus' => AiCorpus::FrontierChats,
                'kind' => AiDocumentKind::ChatTranscript,
                'title' => $fresh->title,
                'imported_conversation_id' => $fresh->id,
                'metadata' => [
                    'source' => $fresh->source->value,
                    'model' => $fresh->model,
                    'revision' => $fresh->revision,
                ],
            ]);

            $rows = [];
            foreach ($embedded as $row) {
                $chunk = AiChunk::query()->create([
                    'document_id' => $document->id,
                    'content' => $row['content'],
                    'position' => $row['position'],
                    'token_count' => $row['token_count'],
                    'embedding_model' => $row['embedding_model'],
                    'metadata' => $row['metadata'],
                ]);

                $rows[] = [
                    'chunk_id' => $chunk->id,
                    'embedding' => $row['embedding'],
                    'embedding_model' => $row['embedding_model'],
                ];
            }

            $fresh->update([
                'status' => AiImportStatus::Ready,
                'error_message' => null,
            ]);

            return [
                'previous_chunk_ids' => $previousChunkIds->all(),
                'embeddings' => $rows,
            ];
        });

        if ($created === []) {
            return;
        }

        if ($created['previous_chunk_ids'] !== []) {
            AiChunkEmbedding::query()->whereIn('chunk_id', $created['previous_chunk_ids'])->delete();
        }

        foreach ($created['embeddings'] as $row) {
            AiChunkEmbedding::query()->create($row);
        }
    }

    protected function estimateTokens(string $content): int
    {
        return max(1, (int) ceil(str_word_count($content) * 1.3));
    }
}
