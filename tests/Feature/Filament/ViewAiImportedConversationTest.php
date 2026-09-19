<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\AiChatSource;
use App\Enums\AiImportStatus;
use App\Filament\Resources\AiImportedConversationResource;
use App\Filament\Resources\AiImportedConversationResource\Pages\ViewAiImportedConversation;
use App\Jobs\ProcessImportedConversation;
use App\Models\AiImportedConversation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewAiImportedConversationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function view_page_can_queue_a_reindex(): void
    {
        Queue::fake();

        $conversation = AiImportedConversation::query()->create([
            'source' => AiChatSource::Gemini,
            'title' => 'Frontier notes',
            'imported_at' => now(),
            'status' => AiImportStatus::Ready,
            'import_key' => 'external:gemini:view-1',
        ]);

        $this->get(AiImportedConversationResource::getUrl('view', [
            'record' => $conversation,
        ]))->assertSuccessful();

        Livewire::test(ViewAiImportedConversation::class, [
            'record' => $conversation->getKey(),
        ])
            ->assertSee('Frontier notes')
            ->callAction('reindex')
            ->assertNotified('Re-index queued');

        Queue::assertPushed(ProcessImportedConversation::class, function (ProcessImportedConversation $job) use ($conversation): bool {
            return $job->conversationId === $conversation->id;
        });
    }
}
