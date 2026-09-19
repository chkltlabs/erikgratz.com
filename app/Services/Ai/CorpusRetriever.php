<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Retrieval\CorpusEmbedder;
use App\Enums\AiCorpus;
use App\Enums\AiImportStatus;
use App\Exceptions\Ai\AiConfigurationException;
use App\Models\AiChunk;
use App\Models\AiChunkEmbedding;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Distance;

class CorpusRetriever
{
    public function __construct(
        protected CorpusEmbedder $embedder,
    ) {}

    /**
     * Ready frontier corpus only — no corpus parameter on the public surface of this class.
     *
     * @return list<array{chunk: AiChunk, score: float, citation: string}>
     */
    public function retrieveReadyFrontier(string $query, ?int $topK = null): array
    {
        $topK ??= (int) config('chatbots.rag.top_k', 6);
        $model = $this->embedder->spec()->model;
        $queryEmbedding = $this->embedder->embed([$query])[0];

        $readyIds = $this->readyFrontierChunkIds();
        $matchingIds = AiChunk::query()
            ->whereIn('id', $readyIds)
            ->where('embedding_model', $model)
            ->pluck('id');

        if ($matchingIds->isEmpty()) {
            if ($readyIds->isNotEmpty() && AiChunkEmbedding::query()->whereIn('chunk_id', $readyIds)->exists()) {
                throw new AiConfigurationException(
                    developerMessage: "Ready frontier chunks exist but none match embedding_model={$model}. Re-index required.",
                );
            }

            return [];
        }

        /** @var Collection<int, AiChunkEmbedding> $neighbors */
        $neighbors = AiChunkEmbedding::query()
            ->where('embedding_model', $model)
            ->whereIn('chunk_id', $matchingIds)
            ->nearestNeighbors('embedding', $queryEmbedding, Distance::Cosine)
            ->take($topK)
            ->get();

        $chunks = AiChunk::query()
            ->with(['document.conversation'])
            ->whereIn('id', $neighbors->pluck('chunk_id'))
            ->get()
            ->keyBy('id');

        $scored = [];
        foreach ($neighbors as $neighbor) {
            $chunk = $chunks->get($neighbor->chunk_id);
            if ($chunk === null) {
                continue;
            }

            $document = $chunk->document;
            $meta = $chunk->metadata ?? [];
            $seqStart = $meta['sequence_start'] ?? null;
            $seqEnd = $meta['sequence_end'] ?? null;
            $range = ($seqStart && $seqEnd) ? " messages {$seqStart}-{$seqEnd}" : '';

            $scored[] = [
                'chunk' => $chunk,
                'score' => 1 - (float) $neighbor->neighbor_distance,
                'citation' => ($document->title ?: 'Untitled').$range,
            ];
        }

        return $scored;
    }

    /**
     * @param  list<array{chunk: AiChunk, score: float, citation: string}>  $hits
     */
    public function formatContext(array $hits): string
    {
        if ($hits === []) {
            return 'No matching imported chat excerpts were found.';
        }

        $blocks = [];
        foreach ($hits as $index => $hit) {
            $n = $index + 1;
            $blocks[] = "[Excerpt {$n} | {$hit['citation']} | score=".number_format($hit['score'], 3)."]\n".$hit['chunk']->content;
        }

        return implode("\n\n---\n\n", $blocks);
    }

    /**
     * @return Collection<int, int>
     */
    protected function readyFrontierChunkIds(): Collection
    {
        return AiChunk::query()
            ->whereHas('document', function ($query) {
                $query->where('corpus', AiCorpus::FrontierChats)
                    ->whereHas('conversation', function ($query) {
                        $query->where('status', AiImportStatus::Ready);
                    });
            })
            ->pluck('id');
    }
}
