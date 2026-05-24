<?php

namespace App\Support;

use InvalidArgumentException;

class MathExpression
{
    private int $pos = 0;

    public static function resolve(string|int|float|null $value): float
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $expression = trim((string) $value);

        if (! preg_match('/^[\d\s\.\+\-\*\/\(\)]+$/', $expression)) {
            throw new InvalidArgumentException('Expression contains invalid characters.');
        }

        $parser = new self($expression);
        $result = $parser->parseExpression();

        $parser->skipWhitespace();
        if ($parser->pos < strlen($expression)) {
            throw new InvalidArgumentException('Invalid expression syntax.');
        }

        return $result;
    }

    private function __construct(private readonly string $input) {}

    private function parseExpression(): float
    {
        $left = $this->parseTerm();

        while (true) {
            $this->skipWhitespace();

            if ($this->pos >= strlen($this->input)) {
                break;
            }

            $char = $this->input[$this->pos];

            if ($char === '+') {
                $this->pos++;
                $left += $this->parseTerm();
            } elseif ($char === '-') {
                $this->pos++;
                $left -= $this->parseTerm();
            } else {
                break;
            }
        }

        return $left;
    }

    private function parseTerm(): float
    {
        $left = $this->parseFactor();

        while (true) {
            $this->skipWhitespace();

            if ($this->pos >= strlen($this->input)) {
                break;
            }

            $char = $this->input[$this->pos];

            if ($char === '*') {
                $this->pos++;
                $left *= $this->parseFactor();
            } elseif ($char === '/') {
                $this->pos++;
                $right = $this->parseFactor();

                if ($right == 0.0) {
                    throw new InvalidArgumentException('Division by zero.');
                }

                $left /= $right;
            } else {
                break;
            }
        }

        return $left;
    }

    private function parseFactor(): float
    {
        $this->skipWhitespace();

        if ($this->pos >= strlen($this->input)) {
            throw new InvalidArgumentException('Unexpected end of expression.');
        }

        $char = $this->input[$this->pos];

        if ($char === '+' || $char === '-') {
            $this->pos++;

            $value = $this->parseFactor();

            return $char === '-' ? -$value : $value;
        }

        if ($char === '(') {
            $this->pos++;
            $value = $this->parseExpression();
            $this->skipWhitespace();

            if ($this->pos >= strlen($this->input) || $this->input[$this->pos] !== ')') {
                throw new InvalidArgumentException('Missing closing parenthesis.');
            }

            $this->pos++;

            return $value;
        }

        return $this->parseNumber();
    }

    private function parseNumber(): float
    {
        $this->skipWhitespace();
        $start = $this->pos;

        while ($this->pos < strlen($this->input) && (ctype_digit($this->input[$this->pos]) || $this->input[$this->pos] === '.')) {
            $this->pos++;
        }

        if ($start === $this->pos) {
            throw new InvalidArgumentException('Expected number.');
        }

        $number = substr($this->input, $start, $this->pos - $start);

        if (! is_numeric($number)) {
            throw new InvalidArgumentException('Invalid number format.');
        }

        return (float) $number;
    }

    private function skipWhitespace(): void
    {
        while ($this->pos < strlen($this->input) && ctype_space($this->input[$this->pos])) {
            $this->pos++;
        }
    }
}
