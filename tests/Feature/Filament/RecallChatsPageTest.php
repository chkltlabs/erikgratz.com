<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Ai\Agents\AdminRecallAgent;
use App\Enums\AiImportStatus;
use App\Filament\Pages\RecallChats;
use App\Jobs\ProcessImportedConversation;
use App\Models\User;
use App\Services\Ai\FrontierChatImporter;
use App\Services\Ai\FrontierChatIndexer;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecallChatsPageTest extends TestCase
{
    #[Test]
    public function ask_appends_user_and_recall_turns(): void
    {
        $this->actingAs(User::factory()->create());

        AdminRecallAgent::fake(fn (string $prompt): string => 'From imported chats: the neon octopus.');
        Embeddings::fake(function (EmbeddingsPrompt $prompt): array {
            return array_map(
                fn (string $text): array => array_fill(0, $prompt->dimensions, 0.1),
                $prompt->inputs,
            );
        });

        $payload = json_encode([
            'title' => 'Secret project chat',
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'cobalt archive']]],
                ['role' => 'model', 'parts' => [['text' => 'The neon octopus guards the cobalt archive']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $conversation = app(FrontierChatImporter::class)->importFromPayload($payload);
        (new ProcessImportedConversation($conversation->id))->handle(app(FrontierChatIndexer::class));
        $this->assertTrue($conversation->fresh()->status->is(AiImportStatus::Ready));

        Livewire::test(RecallChats::class)
            ->fillForm(['question' => 'What guards the cobalt archive?'])
            ->call('ask')
            ->assertSuccessful()
            ->assertSet('transcript', function (array $transcript): bool {
                return count($transcript) === 2
                    && $transcript[0]['role'] === 'user'
                    && $transcript[1]['role'] === 'assistant'
                    && str_contains($transcript[1]['content'], 'neon octopus');
            });
    }
}
