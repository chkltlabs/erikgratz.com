<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static Indexing()
 * @method static static Ready()
 * @method static static Failed()
 */
final class AiImportStatus extends Enum
{
    #[Description('Pending')]
    const Pending = 'pending';

    #[Description('Indexing')]
    const Indexing = 'indexing';

    #[Description('Ready')]
    const Ready = 'ready';

    #[Description('Failed')]
    const Failed = 'failed';
}
