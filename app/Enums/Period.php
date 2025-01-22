<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Period extends Enum
{
    const Yearly = 'yearly';
    const Monthly = 'monthly';
    const Weekly = 'weekly';
}
