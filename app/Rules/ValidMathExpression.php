<?php

namespace App\Rules;

use App\Support\MathExpression;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidMathExpression implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            MathExpression::resolve($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid number or math expression.');
        }
    }
}
