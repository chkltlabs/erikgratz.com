<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static FrontierChats()
 * @method static static WorkHistory()
 */
final class AiCorpus extends Enum
{
    #[Description('Frontier chats')]
    const FrontierChats = 'frontier_chats';

    #[Description('Work history')]
    const WorkHistory = 'work_history';
}
