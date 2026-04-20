# Pre-refactor audit (Phase 1 snapshot)

This document captured the live site surface before the Volt-first prune. See [README.md](README.md) and [routes-and-urls.md](routes-and-urls.md) for the current architecture.

## Surfaces

| Surface | Stack | Entry |
|---------|--------|--------|
| Public marketing | Livewire Volt + `livewire.components.layouts.app` | `/home`, `/work`, `/experience`, `/photo` |
| Legacy public | Inertia + Vue + `resources/views/app.blade.php` | `/contact`, `/portfolio`, `/play`, `/blog`, `/wedding`, `/donate`, `/mock/{page}` |
| Contacts | `ContactController` + Inertia (public index leak) + `POST /contacts` | `routes/web.php` |
| Admin | Filament panel `admin` | `AdminPanelProvider` |
| Auth | Breeze controllers + Inertia Vue pages; GET `login` → Filament | `routes/auth.php` |

## Vite (before)

- Livewire layout incorrectly loaded `resources/js/app.js` (full Inertia + Vue).
- Filament used separate theme/chart inputs in `vite.config.js`.

## Blog

- Public `/blog` and authenticated Inertia `/blog/*` duplicated Filament `BlogPostResource`.
