<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Calendar()
 * @method static static Statement()
 * @method static static CardAnniversary()
 * @method static static Custom()
 */
final class BenefitResetAnchor extends Enum
{
    #[Description('Calendar')]
    const Calendar = 'calendar';

    #[Description('Statement')]
    const Statement = 'statement';

    #[Description('Card anniversary')]
    const CardAnniversary = 'card_anniversary';

    #[Description('Custom')]
    const Custom = 'custom';
}
