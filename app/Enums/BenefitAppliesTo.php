<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Any()
 * @method static static Flight()
 * @method static static Hotel()
 * @method static static Car()
 * @method static static Other()
 */
final class BenefitAppliesTo extends Enum
{
    #[Description('Any travel')]
    const Any = 'any';

    #[Description('Flight')]
    const Flight = 'flight';

    #[Description('Hotel')]
    const Hotel = 'hotel';

    #[Description('Car')]
    const Car = 'car';

    #[Description('Not travel')]
    const Other = 'other';
}
