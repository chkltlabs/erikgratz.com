<?php

declare(strict_types=1);

namespace App\Services\Ai\Parsing;

use App\Enums\AiChatSource;
use App\Enums\AiMessageRole;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

class GoogleTakeoutHtmlParser implements FrontierChatParser
{
    private HtmlConverter $htmlConverter;

    public function __construct(?HtmlConverter $htmlConverter = null)
    {
        if ($htmlConverter !== null) {
            $this->htmlConverter = $htmlConverter;

            return;
        }

        $this->htmlConverter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);
        $this->htmlConverter->getEnvironment()->addConverter(new TableConverter);
    }

    public function supports(string $payload, ?string $filename = null): bool
    {
        if ($filename !== null) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ['html', 'htm'], true)) {
                return true;
            }
        }

        $haystack = substr($payload, 0, 200_000);

        return str_contains($haystack, 'outer-cell')
            && (str_contains($haystack, 'Your prompt:') || str_contains($haystack, 'Search&#39;s response:') || str_contains($haystack, "Search's response:"))
            && (str_contains($haystack, 'My Activity History') || str_contains($haystack, 'AI Mode') || str_contains($haystack, 'content-cell'));
    }

    public function parse(string $payload, ?string $filename = null): ParsedFrontierChat
    {
        $all = $this->parseMany($payload, $filename);
        if ($all === []) {
            throw new InvalidArgumentException('No conversations found in Google Takeout HTML.');
        }

        return $all[0];
    }

    /**
     * @return list<ParsedFrontierChat>
     */
    public function parseMany(string $payload, ?string $filename = null): array
    {
        $conversations = [];

        foreach ($this->outerCellHtmlSnippets($payload) as $cellHtml) {
            $parsed = $this->parseOuterCell($cellHtml, $filename);
            if ($parsed !== null) {
                $conversations[] = $parsed;
            }
        }

        return $conversations;
    }

    /**
     * @return list<string>
     */
    protected function outerCellHtmlSnippets(string $payload): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $wrapped = '<?xml encoding="UTF-8"><div id="takeout-root">'.$payload.'</div>';
        $dom->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " outer-cell ")]');
        if ($nodes === false || $nodes->length === 0) {
            return $this->outerCellHtmlSnippetsByRegex($payload);
        }

        $snippets = [];
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $snippets[] = $dom->saveHTML($node) ?: '';
        }

        return array_values(array_filter($snippets, static fn (string $html): bool => $html !== ''));
    }

    /**
     * @return list<string>
     */
    protected function outerCellHtmlSnippetsByRegex(string $payload): array
    {
        $parts = preg_split('/(?=<div class="outer-cell\b)/i', $payload) ?: [];
        $snippets = [];
        foreach ($parts as $part) {
            if (str_starts_with(ltrim($part), '<div class="outer-cell') || str_starts_with(ltrim($part), "<div class='outer-cell")) {
                $snippets[] = $part;
            }
        }

        return $snippets;
    }

    protected function parseOuterCell(string $cellHtml, ?string $filename): ?ParsedFrontierChat
    {
        if (! preg_match('/<strong>\s*Your prompt:\s*<\/strong>/i', $cellHtml)) {
            return null;
        }

        $timestamp = $this->extractTimestamp($cellHtml);
        $title = $this->extractTitle($cellHtml, $filename);
        $messages = $this->extractMessages($cellHtml, $timestamp);

        if ($messages === []) {
            return null;
        }

        $firstPrompt = '';
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === AiMessageRole::User) {
                $firstPrompt = (string) ($message['content'] ?? '');
                break;
            }
        }

        if ($firstPrompt === '' || $timestamp === null || $timestamp === '') {
            return null;
        }

        $externalId = hash('sha256', $this->normalizeIdentity($firstPrompt).'|'.$this->normalizeIdentity($timestamp));

        return new ParsedFrontierChat(
            title: $title,
            source: AiChatSource::GoogleAiMode,
            model: 'AI Mode',
            externalId: $externalId,
            messages: $messages,
            rawPayload: $cellHtml,
        );
    }

    protected function extractTitle(string $cellHtml, ?string $filename): string
    {
        if (preg_match('/Searched for\s*(?:&nbsp;|\x{00a0}|\s)*<a\b[^>]*>(.*?)<\/a>/iu', $cellHtml, $m) === 1) {
            $title = $this->htmlToMarkdown($m[1]);
            $title = trim(preg_replace('/\[(.*?)\]\([^)]*\)/u', '$1', $title) ?? $title);
            if ($title !== '') {
                return $title;
            }
        }

        if (str_contains($cellHtml, 'Searched with Google Lens')) {
            return 'Google Lens search';
        }

        if ($filename) {
            return pathinfo($filename, PATHINFO_FILENAME);
        }

        return 'AI Mode chat';
    }

    protected function extractTimestamp(string $cellHtml): ?string
    {
        if (preg_match(
            '/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},\s+\d{4},[^\n<]+)/u',
            $cellHtml,
            $m,
        ) === 1) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }

    /**
     * @return list<array{role: string, content: string, occurred_at: ?string}>
     */
    protected function extractMessages(string $cellHtml, ?string $timestamp): array
    {
        if (preg_match(
            '/<div class="content-cell mdl-cell mdl-cell--6-col mdl-typography--body-1">(.*?)<div class="content-cell mdl-cell mdl-cell--6-col mdl-typography--body-1 mdl-typography--text-right">/is',
            $cellHtml,
            $m,
        ) === 1) {
            $body = $m[1];
        } else {
            $body = $cellHtml;
        }

        $parts = preg_split('/<strong>\s*Your prompt:\s*<\/strong>\s*(?:<br\s*\/?>)?/i', $body) ?: [];
        if (count($parts) < 2) {
            return [];
        }

        $messages = [];
        for ($i = 1, $count = count($parts); $i < $count; $i++) {
            $segment = $parts[$i];
            $userHtml = $segment;
            $assistantHtml = '';

            if (preg_match('/^(.*?)<strong>\s*Search(?:&#39;|\')s response:\s*<\/strong>\s*(?:<br\s*\/?>)?(.*)$/is', $segment, $pair) === 1) {
                $userHtml = $pair[1];
                $assistantHtml = $pair[2];
            }

            $userText = $this->htmlToMarkdown($userHtml);
            if ($userText !== '') {
                $messages[] = [
                    'role' => AiMessageRole::User,
                    'content' => $userText,
                    'occurred_at' => $timestamp,
                ];
            }

            $assistantText = $this->htmlToMarkdown($assistantHtml);
            if ($assistantText !== '') {
                $messages[] = [
                    'role' => AiMessageRole::Assistant,
                    'content' => $assistantText,
                    'occurred_at' => $timestamp,
                ];
            }
        }

        return $messages;
    }

    protected function htmlToMarkdown(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $markdown = $this->htmlConverter->convert($html);
        $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $markdown = preg_replace("/\n{3,}/u", "\n\n", $markdown) ?? $markdown;

        return trim($markdown);
    }

    protected function normalizeIdentity(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip light markdown so identity stays stable if conversion wraps plain prompts.
        $value = preg_replace('/[*_`#]+/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($value));
    }
}
