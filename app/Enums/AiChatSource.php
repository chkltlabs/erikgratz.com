<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Gemini()
 * @method static static GoogleAiMode()
 * @method static static Chatgpt()
 * @method static static Claude()
 * @method static static Other()
 */
final class AiChatSource extends Enum
{
    #[Description('Google Gemini')]
    const Gemini = 'gemini';

    #[Description('Google AI Mode / Takeout')]
    const GoogleAiMode = 'google_ai_mode';

    #[Description('ChatGPT')]
    const Chatgpt = 'chatgpt';

    #[Description('Claude')]
    const Claude = 'claude';

    #[Description('Other')]
    const Other = 'other';
}
