<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Income()
 * @method static static Housing()
 * @method static static Transport()
 * @method static static Living()
 * @method static static Cats()
 * @method static static Experience()
 * @method static static Subscription()
 * @method static static Other()
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
