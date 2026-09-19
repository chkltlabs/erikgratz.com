<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static User()
 * @method static static Assistant()
 * @method static static System()
 * @method static static Tool()
 */
final class AiMessageRole extends Enum
{
    #[Description('User')]
    const User = 'user';

    #[Description('Assistant')]
    const Assistant = 'assistant';

    #[Description('System')]
    const System = 'system';

    #[Description('Tool')]
    const Tool = 'tool';
}
