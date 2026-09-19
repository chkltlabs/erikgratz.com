<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Flight()
 * @method static static Hotel()
 * @method static static Car()
 */
final class BookingCategory extends Enum
{
    #[Description('Flight')]
    const Flight = 'flight';

    #[Description('Hotel')]
    const Hotel = 'hotel';

    #[Description('Car')]
    const Car = 'car';
}
