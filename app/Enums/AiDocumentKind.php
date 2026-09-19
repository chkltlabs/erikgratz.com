<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static ChatTranscript()
 * @method static static ChatSummary()
 * @method static static ChatExcerpt()
 */
final class AiDocumentKind extends Enum
{
    #[Description('Chat transcript')]
    const ChatTranscript = 'chat_transcript';

    #[Description('Chat summary')]
    const ChatSummary = 'chat_summary';

    #[Description('Chat excerpt')]
    const ChatExcerpt = 'chat_excerpt';
}
