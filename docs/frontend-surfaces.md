# Frontend surfaces

## Livewire Volt (public site)

- **Classes:** `app/Livewire/Page/*.php` (`Home`, `Work`, `Experience`, `Photo`, `Contact`, `Portfolio`, `Play`).
- **Views:** `resources/views/livewire/page/*.blade.php` (Portfolio reuses `work.blade.php`).
- **Layout:** `resources/views/livewire/components/layouts/app.blade.php` — header, Livewire, Alpine (bundled with Livewire 3).
- **Vite entry:** `resources/js/livewire.js` imports `resources/css/app.css` and `resources/sass/app.scss` only (no Vue/Inertia).

Shared Livewire/Volt fragments live under `resources/views/livewire/components/` (e.g. `header`, `portfolio-entry`, `title-box`).

## Filament admin

- **Provider:** `app/Providers/Filament/AdminPanelProvider.php`
- **Theme / charts:** extra Vite inputs in `vite.config.js` (`resources/css/filament/admin/theme.css`, `resources/js/Filament/filament-chart-plugins.js`, etc.).

## Auth (minimal Blade)

- **Layout component:** `resources/views/components/auth-layout.blade.php`
- **Views:** `resources/views/auth/*.blade.php` (forgot/reset/verify/confirm password)

## Removed

- Inertia + Vue (`resources/js/app.js`, `HandleInertiaRequests`, `inertiajs/inertia-laravel`).
- Breeze Vue auth pages and `laravel/breeze`.
- Public blog and Inertia blog CRUD routes; blog content is managed only via **Filament** `BlogPostResource`.
