<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Currency()
 * @method static static Quantity()
 * @method static static Flag()
 */
final class BenefitValueKind extends Enum
{
    #[Description('Currency')]
    const Currency = 'currency';

    #[Description('Quantity')]
    const Quantity = 'quantity';

    #[Description('Flag')]
    const Flag = 'flag';
}
