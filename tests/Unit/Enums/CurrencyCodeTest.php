<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CurrencyCode;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyCodeTest extends TestCase
{
    #[Test]
    public function trip_currencies_are_available_for_automatic_conversion(): void
    {
        $codes = ['THB', 'VND', 'PHP', 'MYR', 'SGD', 'IDR', 'JPY', 'CNY', 'KRW'];

        foreach ($codes as $code) {
            $this->assertContains($code, config('currency.supported'));
            $this->assertArrayHasKey($code, CurrencyCode::options());
            $this->assertSame(CurrencyCode::from($code), CurrencyCode::tryFromSupported($code));
        }
    }
}
