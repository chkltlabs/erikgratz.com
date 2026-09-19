<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static PublicSite()
 * @method static static Admin()
 */
final class AiSurface extends Enum
{
    #[Description('Public')]
    const PublicSite = 'public';

    #[Description('Admin')]
    const Admin = 'admin';
}
