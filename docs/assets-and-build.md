# Assets and build

## Vite (`vite.config.js`)

**Inputs:**

- `resources/css/app.css` — Tailwind / site styles
- `resources/sass/app.scss` — additional SCSS
- `resources/js/livewire.js` — public Livewire layout entry (CSS imports only)
- `resources/css/filament.css` — Filament-related CSS
- `public/css/filament/filament/app.css` — Filament app styles
- `resources/js/Filament/filament-chart-plugins.js` — registers Chart.js datalabels for Filament charts
- `resources/css/filament/admin/theme.css` — Filament admin theme (see `AdminPanelProvider::viteTheme`)

The Vue plugin was removed; the stack is Vite 6 + `@tailwindcss/vite` + `laravel-vite-plugin`.

## NPM

From repo root:

- **Development:** `npm run dev` (Vite dev server, default port **5174** in config)
- **Production build:** `npm run build` → `public/build`

**Dependencies (trimmed):** Tailwind 4, Vite 6, Sass, `chart.js` + `chartjs-plugin-datalabels` for Filament chart plugins.

## Playground flag

- **Env:** `PLAYGROUND_ENABLED` (see `.env.example`)
- **Config:** `config('app.playground_enabled')` — when false, `GET /play` returns 404.
