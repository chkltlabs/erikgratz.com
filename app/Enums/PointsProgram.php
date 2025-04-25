<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Unknown()
 * @method static static ChaseUltimateRewards()
 * @method static static CapitalOneMiles()
 * @method static static Avios()
 * @method static static Aeroplan()
 * @method static static CitiThankYou()
 * @method static static AmExMemberRewards()
 * @method static static Bilt()
 */
final class PointsProgram extends Enum
{
    #[Description('Unknown')]
    const Unknown = 'unknown';

    #[Description('Chase Ultimate Rewards')]
    const ChaseUltimateRewards = 'chaseUltimateRewards';

    #[Description('Capital One Miles')]
    const CapitalOneMiles = 'capitalOneMiles';

    #[Description('Avios')]
    const Avios = 'avios';

    #[Description('Aeroplan')]
    const Aeroplan = 'aeroplan';

    #[Description('CitiBank ThankYou Points')]
    const CitiThankYou = 'citiThankYou';

    #[Description('American Express Membership Rewards')]
    const AmExMemberRewards = 'amExMemberRewards';

    #[Description('Bilt Rewards')]
    const Bilt = 'bilt';
}
