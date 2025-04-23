<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Checking()
 * @method static static Savings()
 * @method static static MoneyMarket()
 * @method static static Investment()
 */
final class AccountType extends Enum
{
    const Checking = 'Checking';

    const Savings = 'Savings';

    const MoneyMarket = 'MoneyMarket';

    const Investment = 'Investment';
}
