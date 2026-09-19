<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Airline()
 * @method static static Hotel()
 * @method static static Car()
 */
final class LoyaltyKind extends Enum
{
    #[Description('Airline')]
    const Airline = 'airline';

    #[Description('Hotel')]
    const Hotel = 'hotel';

    #[Description('Car')]
    const Car = 'car';
}
