---
paths:
  - '**/*.php'
---

# PHP

PHP 8.4. Follow the style already in the file you are editing.

- Curly braces on every control structure, including one-line bodies.
- Type every parameter and return value. Use constructor property promotion. Do not add an empty public zero-argument constructor.
- Declare model casts in a `casts()` method. Leave a model on `$casts` only when that file already uses `$casts`.
- Put imports at the top of the file. No inline imports.
- Prefer PHPDoc for array shapes. Add an inline comment only when the next line would be unclear without it.
- This app uses `BenSampo\Enum\Enum` (`App\Enums`). Do not introduce a native backed enum beside those.
- New and edited tests use PHPUnit 11 `#[Test]` methods, not a docblock `@test`.
- After editing PHP, run `vendor/bin/pint --dirty --format agent`.
