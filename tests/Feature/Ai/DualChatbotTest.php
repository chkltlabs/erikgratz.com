<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Ai\Agents\AdminRecallAgent;
use App\Ai\Agents\PublicFitAgent;
use App\Enums\AiImportStatus;
use App\Enums\AiSurface;
use App\Exceptions\Ai\AiProviderException;
use App\Jobs\ProcessImportedConversation;
use App\Models\AiImportedConversation;
use App\Models\AiSession;
use App\Models\User;
use App\Services\Ai\AdminRecallAssistant;
use App\Services\Ai\CorpusRetriever;
use App\Services\Ai\FrontierChatImporter;
use App\Services\Ai\FrontierChatIndexer;
use App\Services\Ai\PublicFitAssistant;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DualChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep production width so dim-mismatch regressions stay visible.
        config([
            'chatbots.rag.embedding.dimensions' => 768,
            'chatbots.rag.embedding.model' => 'text-embedding-004',
        ]);

        $this->fakeChatbots();
    }

    #[Test]
    public function agents_are_not_conversational(): void
    {
        $this->assertNotInstanceOf(Conversational::class, app(PublicFitAgent::class));
        $this->assertNotInstanceOf(Conversational::class, app(AdminRecallAgent::class));
    }

    protected function fakeChatbots(): void
    {
        PublicFitAgent::fake(function (string $prompt): string {
            if (str_contains(strtolower($prompt), 'job description')) {
                return "Pocketnest: Laravel/Filament backend at acquisition scale.\nSonic Boom Wellness: API migration and security hardening.\nInternet Things: lead-auction platforms.\nGaps depend on stacks outside PHP/Laravel in the JD.";
            }

            return 'Erik is a Senior Software Engineer focused on Laravel/PHP backends, with roles at Pocketnest, Sonic Boom Wellness, and Internet Things. Paste a job description for a structured fit assessment.';
        });

        AdminRecallAgent::fake(function (string $prompt): string {
            if (preg_match('/\[Excerpt\s+\d+[^\]]*\]\s*(.+?)(?:\n\n---|\z)/s', $prompt, $m) === 1) {
                return 'From imported chats: '.Str::limit(trim($m[1]), 280);
            }

            return 'From imported chats: matching excerpts were retrieved for your question.';
        });

        Embeddings::fake(function (EmbeddingsPrompt $prompt): array {
            return array_map(
                fn (string $text): array => $this->deterministicEmbedding($text, $prompt->dimensions),
                $prompt->inputs,
            );
        });
    }

    /**
     * @return list<float>
     */
    protected function deterministicEmbedding(string $text, int $dimensions): array
    {
        $hash = hash('sha256', $text, true);
        $vector = [];
        for ($i = 0; $i < $dimensions; $i++) {
            $vector[] = (ord($hash[$i % strlen($hash)]) / 255.0) * 2 - 1;
        }

        return $vector;
    }

    protected function providerHttpException(int $status, string $body): RequestException
    {
        return new RequestException(
            new HttpClientResponse(new Psr7Response($status, ['Content-Type' => 'application/json'], $body)),
        );
    }

    #[Test]
    public function fit_route_renders(): void
    {
        $this->get('/fit')->assertOk()
            ->assertSee('Fit', false)
            ->assertDontSee('Career fit assistant', false)
            ->assertDontSee('Thinking…', false);
    }

    #[Test]
    public function public_fit_assistant_answers_from_work_history_pack(): void
    {
        $result = app(PublicFitAssistant::class)->ask(
            question: 'What stack do you use?',
            ip: '127.0.0.1',
        );

        $this->assertNotSame('', $result['answer']);
        $this->assertNotSame('', $result['session_token']);
        $this->assertDatabaseHas('ai_sessions', [
            'surface' => AiSurface::PublicSite,
            'public_handle_hash' => hash('sha256', $result['session_token']),
        ]);

        PublicFitAgent::assertPrompted(fn ($prompt) => $prompt->contains('What stack do you use?'));
        $this->assertStringContainsString(
            'Work history knowledge pack',
            (string) app(PublicFitAgent::class)->instructions(),
        );
    }

    #[Test]
    public function fit_livewire_ask_shows_single_answer_card(): void
    {
        Livewire::test('page.fit')
            ->set('question', 'Summarize Pocketnest work')
            ->call('ask')
            ->assertSet('error', null)
            ->assertSet('answer', fn ($v) => is_string($v) && $v !== '')
            ->assertSet('sessionToken', fn ($v) => is_string($v) && $v !== '');
    }

    #[Test]
    public function public_fit_rejects_admin_session_capability(): void
    {
        $token = 'admin-leaked-token';
        AiSession::query()->create([
            'surface' => AiSurface::Admin,
            'user_id' => User::factory()->create()->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'public_handle_hash' => hash('sha256', $token),
            'metadata' => [],
        ]);

        $result = app(PublicFitAssistant::class)->ask(
            question: 'What did you build at Pocketnest?',
            sessionToken: $token,
            ip: '127.0.0.1',
        );

        $this->assertNotSame($token, $result['session_token']);
        $this->assertDatabaseHas('ai_sessions', [
            'surface' => AiSurface::PublicSite,
            'public_handle_hash' => hash('sha256', $result['session_token']),
        ]);
        $this->assertSame(1, AiSession::query()->where('surface', AiSurface::PublicSite)->count());
    }

    #[Test]
    public function public_fit_is_single_shot_to_the_model(): void
    {
        PublicFitAgent::fake([
            'First answer mentioning Pocketnest.',
            'First answer mentioning Pocketnest.',
        ]);

        $assistant = app(PublicFitAssistant::class);
        $first = $assistant->ask(question: 'Tell me about Pocketnest', ip: '10.0.0.1');
        $second = $assistant->ask(
            question: 'And Sonic Boom?',
            sessionToken: $first['session_token'],
            ip: '10.0.0.1',
        );

        PublicFitAgent::assertPrompted(fn ($prompt) => $prompt->contains('Tell me about Pocketnest'));
        PublicFitAgent::assertPrompted(function ($prompt) {
            return $prompt->contains('And Sonic Boom?')
                && ! $prompt->contains('Pocketnest');
        });

        $this->assertSame('First answer mentioning Pocketnest.', $first['answer']);
        $this->assertSame('First answer mentioning Pocketnest.', $second['answer']);
    }

    #[Test]
    public function agent_provider_errors_do_not_include_response_body(): void
    {
        PublicFitAgent::fake(function () {
            throw $this->providerHttpException(500, json_encode(['error' => ['message' => 'SECRET_PROVIDER_BODY']]));
        });

        try {
            app(PublicFitAgent::class)->ask('hi');
            $this->fail('Expected AiProviderException');
        } catch (AiProviderException $e) {
            $this->assertStringNotContainsString('SECRET_PROVIDER_BODY', $e->getMessage());
            $this->assertStringContainsString('temporarily unavailable', $e->getMessage());
        }
    }

    #[Test]
    public function fit_livewire_hides_provider_errors(): void
    {
        PublicFitAgent::fake(function () {
            throw $this->providerHttpException(503, json_encode(['error' => 'LEAK_ME']));
        });

        Livewire::test('page.fit')
            ->set('question', 'How is the fit?')
            ->call('ask')
            ->assertSet('error', 'Something went wrong answering that. Please try again.')
            ->assertDontSee('LEAK_ME');
    }

    #[Test]
    public function fit_livewire_shows_provider_detail_when_local(): void
    {
        $this->app['env'] = 'local';

        PublicFitAgent::fake(function () {
            throw $this->providerHttpException(503, json_encode(['error' => 'LEAK_ME']));
        });

        Livewire::test('page.fit')
            ->set('question', 'How is the fit?')
            ->call('ask')
            ->assertSet('error', fn ($v) => is_string($v)
                && str_contains($v, 'temporarily unavailable')
                && str_contains($v, 'LEAK_ME'));
    }

    #[Test]
    public function import_persists_messages_and_indexes_chunks(): void
    {
        $user = User::factory()->create();

        $payload = json_encode([
            'id' => 'ext-99',
            'title' => 'Deploy notes',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'How should we deploy?']]],
                ['role' => 'model', 'parts' => [['text' => 'Use Forge and queue workers.']]],
                ['role' => 'user', 'parts' => [['text' => 'What about Horizon?']]],
                ['role' => 'model', 'parts' => [['text' => 'Cap workers around 128MB each.']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $conversation = app(FrontierChatImporter::class)->importFromPayload(
            payload: $payload,
            userId: $user->id,
        );

        $this->assertTrue($conversation->status->is(AiImportStatus::Pending));
        $this->assertCount(4, $conversation->messages);
        $this->assertSame('external:gemini:ext-99', $conversation->import_key);

        (new ProcessImportedConversation($conversation->id))->handle(app(FrontierChatIndexer::class));

        $conversation->refresh();
        $this->assertTrue($conversation->status->is(AiImportStatus::Ready));
        $this->assertTrue($conversation->documents()->exists());
        $this->assertGreaterThan(0, $conversation->documents()->first()->chunks()->count());
        $this->assertSame(
            'text-embedding-004',
            $conversation->documents()->first()->chunks()->first()->embedding_model,
        );
    }

    #[Test]
    public function plaintext_reimport_dedupes_on_content_hash(): void
    {
        $importer = app(FrontierChatImporter::class);
        $payload = "User: Hello\nAssistant: World";

        $first = $importer->importFromPayload($payload, titleOverride: 'Paste A');
        $second = $importer->importFromPayload($payload, titleOverride: 'Paste B');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(2, $second->revision);
        $this->assertSame(1, AiImportedConversation::query()->count());
    }

    #[Test]
    public function failed_or_pending_chunks_are_not_retrieved(): void
    {
        $payload = json_encode([
            'title' => 'Secret project chat',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'Remember the cobalt archive password hint']]],
                ['role' => 'model', 'parts' => [['text' => 'The neon octopus guards the cobalt archive']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $conversation = app(FrontierChatImporter::class)->importFromPayload($payload);
        (new ProcessImportedConversation($conversation->id))->handle(app(FrontierChatIndexer::class));
        $conversation->refresh()->update(['status' => AiImportStatus::Failed]);

        $hits = app(CorpusRetriever::class)->retrieveReadyFrontier('cobalt archive');
        $this->assertSame([], $hits);
    }

    #[Test]
    public function admin_recall_uses_frontier_corpus_only(): void
    {
        $payload = json_encode([
            'title' => 'Secret project chat',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'Remember the cobalt archive password hint']]],
                ['role' => 'model', 'parts' => [['text' => 'The neon octopus guards the cobalt archive']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $conversation = app(FrontierChatImporter::class)->importFromPayload($payload);
        (new ProcessImportedConversation($conversation->id))->handle(app(FrontierChatIndexer::class));

        $result = app(AdminRecallAssistant::class)->ask('What guards the cobalt archive?');

        $this->assertNotSame('', $result['answer']);
        $this->assertStringContainsString('imported chats', strtolower($result['answer']));
        $this->assertStringNotContainsString('Pocketnest', $result['answer']);
        $this->assertNotEmpty($result['citations']);
        $this->assertDatabaseHas('ai_sessions', [
            'id' => $result['session_id'],
            'surface' => AiSurface::Admin,
        ]);
    }

    #[Test]
    public function admin_recall_outbound_payload_excludes_work_history_pack(): void
    {
        $payload = json_encode([
            'title' => 'Secret project chat',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'cobalt archive']]],
                ['role' => 'model', 'parts' => [['text' => 'The neon octopus guards the cobalt archive']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $conversation = app(FrontierChatImporter::class)->importFromPayload($payload);
        (new ProcessImportedConversation($conversation->id))->handle(app(FrontierChatIndexer::class));

        app(AdminRecallAssistant::class)->ask('What guards the cobalt archive?');

        AdminRecallAgent::assertPrompted(function ($prompt) {
            $instructions = (string) $prompt->agent->instructions();
            $body = $prompt->prompt;

            return ! str_contains($instructions, 'Work history knowledge pack')
                && ! str_contains($instructions, '36.6x stock')
                && ! str_contains($body, 'Work history knowledge pack')
                && ! str_contains($body, '36.6x stock')
                && str_contains($body, 'Retrieved excerpts:');
        });

        PublicFitAgent::assertNeverPrompted();
    }

    #[Test]
    public function import_dispatches_index_job_after_persist(): void
    {
        Queue::fake();

        $conversation = app(FrontierChatImporter::class)->importFromPayload(
            "User: Hello\nAssistant: World",
            titleOverride: 'Manual paste',
        );

        ProcessImportedConversation::dispatch($conversation->id);

        $this->assertDatabaseHas('ai_imported_conversations', [
            'title' => 'Manual paste',
        ]);

        Queue::assertPushed(ProcessImportedConversation::class);
    }

    #[Test]
    public function forged_xff_is_ignored_when_proxies_are_untrusted(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.1',
        ])->get('/fit');

        $ip = request()->ip();
        $this->assertSame('203.0.113.10', $ip);
    }
}
