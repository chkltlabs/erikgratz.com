<?php

declare(strict_types=1);

namespace App\Ai\Retrieval;

use Laravel\Ai\Enums\Lab;

/**
 * Provider, model, and dimensions as one indivisible corpus fact.
 */
final readonly class EmbeddingSpec
{
    public function __construct(
        public Lab $provider,
        public string $model,
        public int $dimensions,
    ) {}

    public static function fromConfig(): self
    {
        $chatProvider = (string) config('chatbots.agents.provider', 'gemini');
        $embeddingProvider = config('chatbots.rag.embedding.provider');
        $provider = filled($embeddingProvider) ? (string) $embeddingProvider : $chatProvider;

        $model = (string) config('chatbots.rag.embedding.model', 'text-embedding-004');
        $dimensions = (int) config('chatbots.rag.embedding.dimensions', 768);

        return new self(
            provider: Lab::from($provider),
            model: $model,
            dimensions: $dimensions,
        );
    }
}
