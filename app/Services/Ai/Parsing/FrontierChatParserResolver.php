<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

class FrontierChatParserResolver
{
    /** @param  list<FrontierChatParser>  $parsers */
    public function __construct(
        protected array $parsers = [],
    ) {
        if ($this->parsers === []) {
            $this->parsers = [
                new GoogleTakeoutHtmlParser,
                new GeminiExportParser,
            ];
        }
    }

    public function parse(string $payload, ?string $filename = null, ?string $preferredSource = null): ParsedFrontierChat
    {
        $all = $this->parseAll($payload, $filename, $preferredSource);
        if ($all === []) {
            return $this->withPreferredSource(
                (new PlaintextFrontierChatParser)->parse($payload, $filename),
                $preferredSource,
            );
        }

        return $all[0];
    }

    /**
     * @return list<ParsedFrontierChat>
     */
    public function parseAll(string $payload, ?string $filename = null, ?string $preferredSource = null): array
    {
        foreach ($this->parsers as $parser) {
            if (! $parser->supports($payload, $filename)) {
                continue;
            }

            if ($parser instanceof GoogleTakeoutHtmlParser) {
                // Keep google_ai_mode so re-uploads upsert on a stable import_key.
                return $parser->parseMany($payload, $filename);
            }

            return [$this->withPreferredSource($parser->parse($payload, $filename), $preferredSource)];
        }

        if (trim($payload) === '') {
            return [];
        }

        return [$this->withPreferredSource(
            (new PlaintextFrontierChatParser)->parse($payload, $filename),
            $preferredSource,
        )];
    }

    protected function withPreferredSource(ParsedFrontierChat $parsed, ?string $preferredSource): ParsedFrontierChat
    {
        if ($preferredSource === null || $preferredSource === '') {
            return $parsed;
        }

        return new ParsedFrontierChat(
            title: $parsed->title,
            source: $preferredSource,
            model: $parsed->model,
            externalId: $parsed->externalId,
            messages: $parsed->messages,
            rawPayload: $parsed->rawPayload,
        );
    }
}
