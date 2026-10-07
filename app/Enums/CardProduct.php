<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Csr()
 * @method static static Csp()
 * @method static static Vx()
 * @method static static AmexPlat()
 * @method static static AmexGreen()
 * @method static static Ba()
 * @method static static ChaseAerlingus()
 * @method static static ChaseAeroplan()
 */
final class CardProduct extends Enum
{
    #[Description('Chase Sapphire Reserve')]
    const Csr = 'csr';

    #[Description('Chase Sapphire Preferred')]
    const Csp = 'csp';

    #[Description('Capital One Venture X')]
    const Vx = 'vx';

    #[Description('Amex Plat')]
    const AmexPlat = 'amex_plat';

    #[Description('Amex Green')]
    const AmexGreen = 'amex_green';

    #[Description('Chase British Airways')]
    const Ba = 'ba';

    #[Description('Chase Aer Lingus')]
    const ChaseAerlingus = 'chase_aerlingus';

    #[Description('Chase Aeroplan')]
    const ChaseAeroplan = 'chase_aeroplan';

    public static function fromCardName(string $name): ?self
    {
        $name = strtolower($name);

        return match (true) {
            str_contains($name, 'sapphire reserve') => self::Csr(),
            str_contains($name, 'sapphire preferred') => self::Csp(),
            str_contains($name, 'c1 v x') || str_contains($name, 'venture x') => self::Vx(),
            str_contains($name, 'amex plat') => self::AmexPlat(),
            str_contains($name, 'amex green') => self::AmexGreen(),
            str_contains($name, 'aer lingus') => self::ChaseAerlingus(),
            str_contains($name, 'aeroplan') => self::ChaseAeroplan(),
            str_contains($name, 'chase ba') || str_contains($name, 'british airways') => self::Ba(),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function migratedFlagNames(): array
    {
        return match ($this->value) {
            self::Csr => ['IHG Platinum Elite status'],
            self::AmexPlat => ['Hilton Honors Gold', 'Marriott Bonvoy Gold'],
            self::Ba => ['10% off BA flights from the US'],
            self::ChaseAeroplan => ['Aeroplan 25K status', '15% off Air Canada awards', 'Free checked bags on Air Canada'],
            default => [],
        };
    }
}
