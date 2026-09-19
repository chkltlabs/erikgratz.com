<?php

declare(strict_types=1);

namespace App\Ai\Retrieval;

use App\Ai\ProviderFailure;
use App\Exceptions\Ai\AiProviderException;
use Laravel\Ai\Embeddings;
use Throwable;

/**
 * The only caller of Embeddings::for() in the application.
 */
final class CorpusEmbedder
{
    public function __construct(
        private readonly EmbeddingSpec $spec,
    ) {}

    public function spec(): EmbeddingSpec
    {
        return $this->spec;
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $batchSize = max(1, (int) config('chatbots.rag.embedding.batch_size', 32));
        $timeout = (int) config('chatbots.rag.embedding.timeout', 60);
        $vectors = [];

        foreach (array_chunk($texts, $batchSize) as $batch) {
            try {
                $response = Embeddings::for($batch)
                    ->dimensions($this->spec->dimensions)
                    ->timeout($timeout)
                    ->generate($this->spec->provider, $this->spec->model);
            } catch (Throwable $e) {
                throw ProviderFailure::translate($e);
            }

            if (count($response) !== count($batch)) {
                throw new AiProviderException(
                    developerMessage: 'Embedding count mismatch: expected '.count($batch).', got '.count($response).'.',
                );
            }

            foreach ($response->embeddings as $index => $vector) {
                if (count($vector) !== $this->spec->dimensions) {
                    throw new AiProviderException(
                        developerMessage: 'Embedding dimension mismatch at batch index '.$index
                            .': expected '.$this->spec->dimensions.', got '.count($vector).'.',
                    );
                }
                $vectors[] = $vector;
            }
        }

        return $vectors;
    }
}
