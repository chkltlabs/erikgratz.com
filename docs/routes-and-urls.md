# Routes and URLs

## Public web (`routes/web.php`)

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/` | — | Redirect to `/home` |
| GET | `/home` | `home` | Volt `page.home` → `App\Livewire\Page\Home` |
| GET | `/work` | — | Volt `page.work` → `App\Livewire\Page\Work` |
| GET | `/experience` | — | Volt `page.experience` → `App\Livewire\Page\Experience` |
| GET | `/photo` | — | Volt `page.photo` → `App\Livewire\Page\Photo` |
| GET | `/contact` | `contact` | Volt `page.contact` → `App\Livewire\Page\Contact` |
| GET | `/portfolio` | — | Volt `page.portfolio` → `App\Livewire\Page\Portfolio` (reuses work view) |
| GET | `/play` | — | Volt `page.play` → `App\Livewire\Page\Play` (404 unless `PLAYGROUND_ENABLED=true`) |

## Authenticated web

| Method | Path | Name | Middleware | Handler |
|--------|------|------|------------|---------|
| GET | `/dashboard` | `dashboard` | `auth`, `verified` | Redirect to Filament dashboard |

## Auth (`routes/auth.php`)

| Method | Path | Name | Notes |
|--------|------|------|--------|
| GET | `/login` | `login` | Redirect to `filament.admin.auth.login` |
| GET/POST | `/forgot-password` | `password.request` / `password.email` | Blade + Laravel Password broker |
| GET/POST | `/reset-password/{token}` | `password.reset` / `password.update` | Blade; success → Filament login |
| GET | `/verify-email` | `verification.notice` | Blade |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | signed |
| POST | `/email/verification-notification` | `verification.send` | |
| GET/POST | `/confirm-password` | `password.confirm` | Blade |
| POST | `/logout` | `logout` | Web guard logout → `/` |

## API (`routes/api.php`)

| Method | Path | Notes |
|--------|------|--------|
| GET | `/api/user` | `auth:api` (legacy token guard) |

The former `contactapi` JSON resource was removed; contact submissions are stored via the Volt contact form and listed in Filament.

## Filament

Panel path: **`/admin`** (`AdminPanelProvider`, id `admin`). Resource URLs follow `filament.admin.resources.*` (e.g. `admin/contacts` for contact form submissions).
