<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static NoReset()
 * @method static static Daily()
 * @method static static Weekly()
 * @method static static Monthly()
 * @method static static Quarterly()
 * @method static static SemiAnnual()
 * @method static static CalendarYearly()
 * @method static static RenewalYearly()
 */
final class ResetPeriod extends Enum
{
    #[Description('No reset')]
    const NoReset = 'no_reset';

    #[Description('Daily')]
    const Daily = 'daily';

    #[Description('Weekly')]
    const Weekly = 'weekly';

    #[Description('Monthly')]
    const Monthly = 'monthly';

    #[Description('Quarterly')]
    const Quarterly = 'quarterly';

    #[Description('Semi-annual')]
    const SemiAnnual = 'semi_annual';

    #[Description('Calendar year')]
    const CalendarYearly = 'calendar_yearly';

    #[Description('Card year')]
    const RenewalYearly = 'renewal_yearly';
}
