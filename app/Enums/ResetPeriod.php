<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static NoReset()
 * @method static static Daily()
 * @method static static Weekly()
 * @method static static Monthly()
 * @method static static CalendarYearly()
 * @method static static RenewalYearly()
 */
final class ResetPeriod extends Enum
{
    const NoReset = 'no_reset';
    const Daily = 'daily';
    const Weekly = 'weekly';
    const Monthly = 'monthly';
    const CalendarYearly = 'calendar_yearly';
    const RenewalYearly = 'renewal_yearly';
}
