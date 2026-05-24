<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencyCode: string
{
    case USD = 'USD';
    case CAD = 'CAD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case AUD = 'AUD';
    case CHF = 'CHF';
    case JPY = 'JPY';
    case MXN = 'MXN';
    case NZD = 'NZD';
    case SEK = 'SEK';
    case NOK = 'NOK';
    case DKK = 'DKK';
    case SGD = 'SGD';
    case HKD = 'HKD';

    public static function default(): self
    {
        return self::USD;
    }

    public function isUsd(): bool
    {
        return $this === self::USD;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::supportedCases() as $case) {
            $options[$case->value] = $case->value;
        }

        return $options;
    }

    /**
     * @return list<self>
     */
    public static function supportedCases(): array
    {
        $supported = config('currency.supported', []);

        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => in_array($case->value, $supported, true),
        ));
    }

    public static function tryFromSupported(string $value): ?self
    {
        $code = self::tryFrom(strtoupper($value));

        if ($code === null) {
            return null;
        }

        return in_array($code->value, config('currency.supported', []), true) ? $code : null;
    }
}
