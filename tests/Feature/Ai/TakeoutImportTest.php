<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Jobs\ProcessImportedConversation;
use App\Models\AiImportedConversation;
use App\Services\Ai\FrontierChatImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TakeoutImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function takeout_html_imports_multiple_conversations(): void
    {
        Queue::fake();

        $payload = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $this->assertNotFalse($payload);

        $result = app(FrontierChatImporter::class)->importAllFromPayload(
            payload: $payload,
            filename: 'MyActivity.html',
        );

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertCount(2, $result['conversations']);
        $this->assertSame(2, AiImportedConversation::query()->count());

        foreach ($result['conversations'] as $conversation) {
            ProcessImportedConversation::dispatch($conversation->id);
        }

        Queue::assertPushed(ProcessImportedConversation::class, 2);
    }

    #[Test]
    public function takeout_reupload_upserts_continued_conversation(): void
    {
        Queue::fake();

        $initial = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode.html'));
        $continued = file_get_contents(base_path('tests/fixtures/ai/takeout-ai-mode-continued.html'));
        $this->assertNotFalse($initial);
        $this->assertNotFalse($continued);

        $importer = app(FrontierChatImporter::class);
        $first = $importer->importAllFromPayload($initial, filename: 'MyActivity.html');
        $wheelchair = collect($first['conversations'])
            ->firstWhere('title', 'wheelchair assistance in ist airport');
        $this->assertNotNull($wheelchair);

        $originalId = $wheelchair->id;

        $second = $importer->importAllFromPayload($continued, filename: 'MyActivity.html');

        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['updated']);
        $this->assertSame($originalId, $second['conversations'][0]->id);
        $this->assertSame(2, (int) $second['conversations'][0]->revision);
        $this->assertTrue(
            $second['conversations'][0]->messages()
                ->where('content', 'like', '%Where to request in mobile app%')
                ->exists(),
        );
        $this->assertSame(2, AiImportedConversation::query()->count());

        ProcessImportedConversation::dispatch($second['conversations'][0]->id);
        Queue::assertPushed(ProcessImportedConversation::class, 1);
    }
}
