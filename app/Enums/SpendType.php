<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SpendType extends Enum
{
    const Income = 'income';

    const Housing = 'housing';

    const Transport = 'transport';

    const Living = 'living';

    const Cats = 'cats';

    const Experience = 'experience';

    const Subscription = 'subscription';

    const Other = 'other';
}
