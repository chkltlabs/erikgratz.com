<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Manual()
 * @method static static AssumedAuto()
 */
final class BenefitUsageSource extends Enum
{
    #[Description('Manual')]
    const Manual = 'manual';

    #[Description('Assumed auto')]
    const AssumedAuto = 'assumed_auto';
}
