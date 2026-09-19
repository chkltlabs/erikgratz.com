<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Charge()
 * @method static static UseBenefit()
 * @method static static Status()
 * @method static static Transfer()
 * @method static static Cash()
 */
final class RecommendationAction extends Enum
{
    #[Description('Charge card')]
    const Charge = 'charge';

    #[Description('Use benefit')]
    const UseBenefit = 'use_benefit';

    #[Description('Loyalty status')]
    const Status = 'status';

    #[Description('Transfer points')]
    const Transfer = 'transfer';

    #[Description('Pay cash')]
    const Cash = 'cash';
}
