<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Economy()
 * @method static static PremiumEconomy()
 * @method static static Business()
 * @method static static First()
 */
final class BookingCabin extends Enum
{
    #[Description('Economy')]
    const Economy = 'economy';

    #[Description('Premium Economy')]
    const PremiumEconomy = 'premium_economy';

    #[Description('Business')]
    const Business = 'business';

    #[Description('First')]
    const First = 'first';
}
