# Site documentation (for humans and agents)

Start here for a map of routes, frontends, auth, and how assets are built.

| Document | Purpose |
|----------|---------|
| [routes-and-urls.md](routes-and-urls.md) | HTTP routes, names, middleware, handlers |
| [frontend-surfaces.md](frontend-surfaces.md) | Volt vs Filament vs auth Blade |
| [assets-and-build.md](assets-and-build.md) | Vite inputs, CSS, `npm` commands |
| [auth-and-admin.md](auth-and-admin.md) | Login, password reset, Filament panel |
| [audit-inventory.md](audit-inventory.md) | Snapshot of the pre-prune architecture |

The public marketing site is **Livewire Volt** (`app/Livewire/Page/*`). The admin surface is **Filament** at `/admin`. **Inertia and Breeze Vue** have been removed.
