<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\AiChatSource;
use App\Enums\AiCorpus;
use App\Enums\AiDocumentKind;
use App\Enums\AiImportStatus;
use App\Enums\AiMessageRole;
use App\Enums\AiSurface;
use App\Models\AiChunk;
use App\Models\AiChunkEmbedding;
use App\Models\AiDocument;
use App\Models\AiImportedConversation;
use App\Models\AiImportedMessage;
use App\Models\AiSession;
use App\Models\AiSessionMessage;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiConversationGraphTest extends TestCase
{
    #[Test]
    public function imported_conversation_exposes_user_messages_and_documents(): void
    {
        $user = User::factory()->create();
        $conversation = AiImportedConversation::query()->create([
            'source' => AiChatSource::Gemini,
            'title' => 'Architecture notes',
            'imported_at' => now(),
            'status' => AiImportStatus::Ready,
            'import_key' => 'external:gemini:conv-1',
            'user_id' => $user->id,
        ]);
        $message = AiImportedMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => AiMessageRole::User,
            'content' => 'Summarize the two bots',
            'sequence' => 0,
        ]);
        $document = AiDocument::query()->create([
            'corpus' => AiCorpus::FrontierChats,
            'kind' => AiDocumentKind::ChatTranscript,
            'title' => 'Architecture notes',
            'imported_conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($conversation->user->is($user));
        $this->assertTrue($conversation->messages->first()->is($message));
        $this->assertTrue($conversation->document->is($document));
        $this->assertTrue($conversation->documents->first()->is($document));
        $this->assertTrue($message->conversation->is($conversation));
    }

    #[Test]
    public function deleting_a_conversation_removes_chunk_embeddings(): void
    {
        $conversation = AiImportedConversation::query()->create([
            'source' => AiChatSource::Claude,
            'title' => 'Delete me',
            'imported_at' => now(),
            'status' => AiImportStatus::Ready,
            'import_key' => 'external:claude:conv-delete',
        ]);
        $document = AiDocument::query()->create([
            'corpus' => AiCorpus::FrontierChats,
            'kind' => AiDocumentKind::ChatExcerpt,
            'title' => 'Delete me',
            'imported_conversation_id' => $conversation->id,
        ]);
        $chunk = AiChunk::query()->create([
            'document_id' => $document->id,
            'content' => 'Keep the two agents isolated.',
            'position' => 0,
        ]);
        AiChunkEmbedding::query()->create([
            'chunk_id' => $chunk->id,
            'embedding' => array_fill(0, 768, 0.01),
            'embedding_model' => 'text-embedding-004',
        ]);

        $conversation->delete();

        $this->assertDatabaseMissing('ai_imported_conversations', ['id' => $conversation->id]);
        $this->assertSame(0, AiChunkEmbedding::query()->where('chunk_id', $chunk->id)->count());
    }

    #[Test]
    public function session_owns_ordered_messages(): void
    {
        $user = User::factory()->create();
        $session = AiSession::query()->create([
            'surface' => AiSurface::Admin,
            'user_id' => $user->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'metadata' => ['origin' => 'test'],
        ]);
        $first = AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::User,
            'content' => 'What did I decide?',
            'sequence' => 1,
        ]);
        $second = AiSessionMessage::query()->create([
            'session_id' => $session->id,
            'role' => AiMessageRole::Assistant,
            'content' => 'Keep Fit single-shot.',
            'sequence' => 0,
        ]);

        $this->assertTrue($session->user->is($user));
        $this->assertTrue($session->messages->first()->is($second));
        $this->assertTrue($session->messages->last()->is($first));
        $this->assertTrue($first->session->is($session));
    }
}
