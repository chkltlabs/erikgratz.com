# Auth and admin

## Filament (primary)

- **URL:** `/admin`
- **Login:** `GET /admin/login` — route name `filament.admin.auth.login`
- **Logout:** `POST /admin/logout` — `filament.admin.auth.logout`
- **Dashboard:** `filament.admin.pages.dashboard` (after login)

Application `RouteServiceProvider::HOME` is **`/admin`** for post-auth redirects (e.g. email verification, password confirmation).

## Web guard / `login` route

- **`GET /login`** redirects to the Filament login page (no Breeze Vue login).

## Password reset

Laravel’s password broker + **Blade** views under `resources/views/auth/`:

- Request link: `/forgot-password`
- Reset form: `/reset-password/{token}`

After a successful reset, users are sent to **Filament login** (`filament.admin.auth.login`).

## Email verification

- Prompt: `/verify-email` (Blade)
- Signed verify URL: `/verify-email/{id}/{hash}`

## Logout (`web` guard)

- **`POST /logout`** clears the session and redirects to **`/`** (see `AuthenticatedSessionController@destroy`).

## Registration

Public **registration routes were removed**. Admin users are expected to be created via Filament or other internal processes.

## Contact submissions (Filament)

Inbound messages from the public contact form appear in **Contact requests** — `App\Filament\Resources\Contacts\ContactResource` at `/admin/contacts`.
