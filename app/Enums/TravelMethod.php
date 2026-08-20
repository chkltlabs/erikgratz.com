<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Attributes\Description;
use BenSampo\Enum\Enum;

/**
 * @method static static Plane()
 * @method static static Car()
 * @method static static Train()
 * @method static static Ferry()
 */
final class TravelMethod extends Enum
{
    #[Description('Plane')]
    const Plane = 'plane';

    #[Description('Car')]
    const Car = 'car';

    #[Description('Train')]
    const Train = 'train';

    #[Description('Ferry')]
    const Ferry = 'ferry';

    /**
     * Hex colors for map route rendering.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            self::Plane => '#2563eb',
            self::Car => '#d97706',
            self::Train => '#16a34a',
            self::Ferry => '#06b6d4',
        ];
    }

    public function color(): string
    {
        return self::colors()[$this->value] ?? '#6b7280';
    }
}
