<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static PendingReview()
 * @method static static Active()
 * @method static static Rejected()
 * @method static static Expired()
 */
final class PromoStatus extends Enum
{
    #[Description('Pending review')]
    const PendingReview = 'pending_review';

    #[Description('Active')]
    const Active = 'active';

    #[Description('Rejected')]
    const Rejected = 'rejected';

    #[Description('Expired')]
    const Expired = 'expired';
}
