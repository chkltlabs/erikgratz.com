<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Track()
 * @method static static Ignore()
 * @method static static Auto()
 */
final class BenefitTrackingMode extends Enum
{
    #[Description('Track')]
    const Track = 'track';

    #[Description('Ignore')]
    const Ignore = 'ignore';

    #[Description('Auto')]
    const Auto = 'auto';
}
