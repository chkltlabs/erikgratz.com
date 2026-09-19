<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiImportedMessage;

class ConversationChunker
{
    /**
     * @param  iterable<int, AiImportedMessage>  $messages
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    public function chunkMessages(iterable $messages): array
    {
        $window = (int) config('chatbots.rag.messages_per_chunk', 4);
        $overlap = (int) config('chatbots.rag.chunk_overlap_messages', 1);
        $items = collect($messages)->values();
        $chunks = [];

        if ($items->isEmpty()) {
            return [];
        }

        $step = max(1, $window - $overlap);
        for ($start = 0; $start < $items->count(); $start += $step) {
            $slice = $items->slice($start, $window)->values();

            $lines = [];
            $messageIds = [];
            $speakers = [];
            foreach ($slice as $message) {
                $role = $message->role->value;
                $lines[] = strtoupper($role).': '.$message->content;
                $messageIds[] = $message->id;
                $speakers[] = $role;
            }

            $chunks[] = [
                'content' => implode("\n\n", $lines),
                'metadata' => [
                    'message_ids' => $messageIds,
                    'speakers' => array_values(array_unique($speakers)),
                    'sequence_start' => $slice->first()->sequence,
                    'sequence_end' => $slice->last()->sequence,
                ],
            ];

            if ($start + $window >= $items->count()) {
                break;
            }
        }

        return $chunks;
    }
}
