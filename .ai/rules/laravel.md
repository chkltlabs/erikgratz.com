---
paths:
  - 'app/**/*.php'
  - 'bootstrap/**/*.php'
  - 'config/**/*.php'
  - 'database/**/*.php'
  - 'routes/**/*.php'
  - 'tests/**/*.php'
  - 'resources/views/**/*.blade.php'
---

# Laravel

Laravel 12 on the Laravel 10 layout. Leave `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, `app/Console/Kernel.php`, and `app/Providers/` where they are.

- Create files with `./vendor/bin/sail artisan make:… --no-interaction`. A feature test is `make:test --phpunit {Name}` with no `Feature/` prefix (`Filament/LoansDueWidgetTest`, not `Feature/Filament/LoansDueWidgetTest`).
- Do not add Composer or npm dependencies without approval.
- Link with named routes (`route()`). New JSON APIs use Eloquent API resources; an existing route keeps its current shape.
- If a migration for a table is still uncommitted, edit that migration (and rename the file if its purpose changed). When altering a column, repeat every attribute it already has.
- Public pages are Livewire Volt single-file components.
- Filament 4 actions are `Filament\Actions\Action`. They are not under `Filament\Tables\Actions` or `Filament\Forms\Actions`.
- Register panel widgets as class strings. `Widget::make()` breaks `array_search(Class::class, $panel->getWidgets(), true)`.

## Tests

- `Tests\TestCase` already uses `RefreshDatabase` for mysql and pgsql. Do not switch a test to `DatabaseTransactions`.
- Create models with factories and factory states. Do not hand-insert rows.
- Run `./vendor/bin/sail artisan test --compact` with a file path or `--filter`. Host `php artisan test` cannot resolve the `mysql` hostname.
- Filament widgets: `actingAs(User::factory()->create())`, then `Livewire::test(Widget::class)`. Call protected chart methods through `ReflectionMethod`.
- A Filament resource test extends the matching class in `tests/Feature/Filament/Parent` when one fits.
- A pull request fails when changed `app/**/*.php` files are under 80% line coverage. The branch threshold is 90%. Cover the changed behavior and the failure mode next to it.
