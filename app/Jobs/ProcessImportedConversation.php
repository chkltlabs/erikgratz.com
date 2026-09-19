<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AiImportStatus;
use App\Models\AiImportedConversation;
use App\Services\Ai\FrontierChatIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessImportedConversation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 600;

    public function __construct(
        public int $conversationId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->conversationId;
    }

    public function handle(FrontierChatIndexer $indexer): void
    {
        $conversation = AiImportedConversation::query()->find($this->conversationId);
        if (! $conversation) {
            return;
        }

        try {
            $indexer->index($conversation);
        } catch (Throwable $e) {
            $conversation->refresh();
            $conversation->update([
                'status' => AiImportStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
