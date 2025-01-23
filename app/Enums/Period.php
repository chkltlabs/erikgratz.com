<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Period extends Enum
{
    const Yearly = 'yearly';
    const Monthly = 'monthly';
    const Weekly = 'weekly';
    const Daily = 'daily';

    public static function getCarbonMethods(Period $period): array
    {
        return match ($period->value) {
            Period::Daily => ['addDay', 'startOfDay', 'endOfDay'],
            Period::Weekly => ['addWeek', 'startOfWeek', 'endOfWeek'],
            Period::Monthly => ['addMonth', 'startOfMonth', 'endOfMonth'],
            Period::Yearly => ['addYear', 'startOfYear', 'endOfYear'],
        };
    }
}
