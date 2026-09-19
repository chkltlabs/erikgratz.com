<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Enums\AiChatSource;
use App\Enums\AiMessageRole;
use App\Services\Ai\Parsing\FrontierChatParserResolver;
use App\Services\Ai\Parsing\GeminiExportParser;
use App\Services\Ai\Parsing\GoogleTakeoutHtmlParser;
use App\Services\Ai\Parsing\PlaintextFrontierChatParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontierChatParserTest extends TestCase
{
    #[Test]
    public function gemini_parser_reads_contents_json(): void
    {
        $payload = json_encode([
            'id' => 'conv-1',
            'title' => 'Architecture notes',
            'model' => 'gemini-2.0-flash',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'Summarize the plan']]],
                ['role' => 'model', 'parts' => [['text' => 'Use two isolated bots']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $parsed = (new GeminiExportParser)->parse($payload);

        $this->assertSame('Architecture notes', $parsed->title);
        $this->assertSame('conv-1', $parsed->externalId);
        $this->assertCount(2, $parsed->messages);
        $this->assertSame('user', $parsed->messages[0]['role']);
        $this->assertSame('assistant', $parsed->messages[1]['role']);
    }

    #[Test]
    public function plaintext_parser_splits_role_prefixed_lines(): void
    {
        $payload = "User: Hello\nAssistant: Hi there\nUser: Thanks";

        $parsed = (new PlaintextFrontierChatParser)->parse($payload, 'notes.txt');

        $this->assertSame('notes', $parsed->title);
        $this->assertCount(3, $parsed->messages);
        $this->assertSame('Thanks', $parsed->messages[2]['content']);
    }

    #[Test]
    public function resolver_prefers_gemini_json_over_plaintext(): void
    {
        $payload = json_encode([
            'title' => 'JSON chat',
            'messages' => [
                ['role' => 'user', 'content' => 'ping'],
                ['role' => 'assistant', 'content' => 'pong'],
            ],
        ], JSON_THROW_ON_ERROR);

        $parsed = (new FrontierChatParserResolver)->parse($payload);

        $this->assertSame('JSON chat', $parsed->title);
        $this->assertSame('gemini', $parsed->source);
    }

    #[Test]
    public function takeout_html_parser_splits_cards_and_pairs_roles(): void
    {
        $payload = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $this->assertNotFalse($payload);

        $parser = new GoogleTakeoutHtmlParser;
        $this->assertTrue($parser->supports($payload, 'MyActivity.html'));

        $all = $parser->parseMany($payload, 'MyActivity.html');

        $this->assertCount(2, $all);
        $this->assertSame('wheelchair assistance in ist airport', $all[0]->title);
        $this->assertSame('Vancouver to Seattle ferry', $all[1]->title);
        $this->assertSame(AiChatSource::GoogleAiMode, $all[0]->source);
        $this->assertNotNull($all[0]->externalId);
        $this->assertCount(6, $all[0]->messages);
        $this->assertSame(AiMessageRole::User, $all[0]->messages[0]['role']);
        $this->assertSame(AiMessageRole::Assistant, $all[0]->messages[1]['role']);
        $this->assertStringContainsString('Istanbul Airport', $all[0]->messages[1]['content']);
        $this->assertCount(2, $all[1]->messages);
    }

    #[Test]
    public function takeout_html_converts_tables_and_lists_to_markdown(): void
    {
        $payload = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $this->assertNotFalse($payload);

        $assistant = (new GoogleTakeoutHtmlParser)->parseMany($payload)[0]->messages[5]['content'];

        $this->assertStringContainsString('|', $assistant);
        $this->assertStringContainsString('---', $assistant);
        $this->assertStringContainsString('0 – 150,000', $assistant);
        $this->assertStringContainsString('5%', $assistant);
        $this->assertDoesNotMatchRegularExpression('/0 – 150,0005%/', $assistant);
        $this->assertMatchesRegularExpression('/^\s*[-*]\s+Applied to net taxable income/m', $assistant);
    }

    #[Test]
    public function takeout_html_external_id_stable_when_conversation_continues(): void
    {
        $initial = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $continued = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode-continued.html'));
        $this->assertNotFalse($initial);
        $this->assertNotFalse($continued);

        $parser = new GoogleTakeoutHtmlParser;
        $first = $parser->parseMany($initial)[0];
        $second = $parser->parseMany($continued)[0];

        $this->assertSame($first->externalId, $second->externalId);
        $this->assertStringContainsString('tax brackets table', $first->messages[4]['content']);
        $this->assertStringContainsString('Where to request in mobile app', $second->messages[4]['content']);
    }

    #[Test]
    public function resolver_parse_all_returns_takeout_conversations(): void
    {
        $payload = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $this->assertNotFalse($payload);

        $all = (new FrontierChatParserResolver)->parseAll($payload, 'MyActivity.html', AiChatSource::Gemini);

        $this->assertCount(2, $all);
        $this->assertSame(AiChatSource::GoogleAiMode, $all[0]->source);
    }
}
