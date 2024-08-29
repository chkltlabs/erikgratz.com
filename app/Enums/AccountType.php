<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class AccountType extends Enum
{
    const Checking = 'Checking';
    const Savings = 'Savings';
    const MoneyMarket = 'MoneyMarket';
    const Investment = 'Investment';
}
