<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Direct()
 * @method static static ChaseTravel()
 * @method static static AmexTravel()
 * @method static static CapitalOneTravel()
 */
final class BookingChannel extends Enum
{
    #[Description('Direct')]
    const Direct = 'direct';

    #[Description('Chase Travel')]
    const ChaseTravel = 'chase_travel';

    #[Description('Amex Travel')]
    const AmexTravel = 'amex_travel';

    #[Description('Capital One Travel')]
    const CapitalOneTravel = 'capital_one_travel';
}
