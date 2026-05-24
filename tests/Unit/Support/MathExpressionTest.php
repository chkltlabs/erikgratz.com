<?php

namespace Tests\Unit\Support;

use App\Support\MathExpression;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MathExpressionTest extends TestCase
{
    #[Test]
    #[DataProvider('validExpressionProvider')]
    public function it_resolves_valid_expressions(mixed $input, float $expected): void
    {
        $this->assertSame($expected, MathExpression::resolve($input));
    }

    public static function validExpressionProvider(): array
    {
        return [
            'null' => [null, 0.0],
            'empty string' => ['', 0.0],
            'whitespace only' => ['   ', 0.0],
            'integer input' => [1500, 1500.0],
            'float input' => [12.5, 12.5],
            'plain numeric string' => ['1500', 1500.0],
            'decimal string' => ['1500.50', 1500.5],
            'addition and subtraction' => ['1500 - 200', 1300.0],
            'addition' => ['100 + 50', 150.0],
            'multiplication' => ['10 * 5', 50.0],
            'division' => ['100 / 4', 25.0],
            'operator precedence' => ['2 + 3 * 4', 14.0],
            'parentheses' => ['(100 + 50) / 2', 75.0],
            'whitespace tolerance' => ['1500 - 200', 1300.0],
            'unary minus' => ['-200', -200.0],
            'unary plus' => ['+200', 200.0],
            'complex expression' => ['(1500 - 200) * 2', 2600.0],
        ];
    }

    #[Test]
    #[DataProvider('invalidExpressionProvider')]
    public function it_rejects_invalid_expressions(mixed $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        MathExpression::resolve($input);
    }

    public static function invalidExpressionProvider(): array
    {
        return [
            'invalid characters' => ['1500 - abc'],
            'missing operand' => ['1500 -'],
            'unbalanced parentheses' => ['(1500 - 200'],
            'empty parentheses' => ['()'],
            'division by zero' => ['100 / 0'],
            'unsupported operator' => ['1500 ^ 200'],
        ];
    }
}
