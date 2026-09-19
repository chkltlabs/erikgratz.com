<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\AiChatSource;
use App\Filament\Resources\AiImportedConversationResource\Pages\ListAiImportedConversations;
use App\Models\AiImportedConversation;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportFrontierChatTest extends TestCase
{
    #[Test]
    public function header_action_imports_a_pasted_conversation(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListAiImportedConversations::class)
            ->callAction('import', [
                'source' => AiChatSource::Gemini,
                'title' => 'Manual paste',
                'payload' => "User: Hello\nAssistant: World",
            ])
            ->assertNotified();

        $this->assertDatabaseHas('ai_imported_conversations', [
            'title' => 'Manual paste',
        ]);
        $this->assertSame(1, AiImportedConversation::query()->count());
    }
}
