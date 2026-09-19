# Admin login surface

Operators reach Filament through `/admin/login` (also linked from the public header logo). This feature only proves the login page is reachable, not a successful authenticated session.

## Sub-features

- `admin-login-render` — `/admin/login` returns the Filament sign-in UI.
- `admin-login-redirect` — `/login` redirects toward Filament login.

## How to get to it (user POV)

- Open `/admin/login`.
- Open `/login`.
- Click the chocolate-lab logo in the public header (links to `/admin/login`).

## Driving it with Chrome DevTools MCP

Preconditions:

- Doctor ok (`bin/doctor.sh --admin` also ok).

- **Open.** Navigate to `{BASE}/admin/login`. Page shows Filament login (email/password fields or “Sign in”).
- **Legacy redirect.** `bin/http-get.sh /login` follows or lands such that admin login is reachable (document final URL/status).
- **Proof.** Screenshot `admin-login.png` under `artifacts/admin-login/<run-id>/`. Do not type real passwords into artifacts.

## Gotchas

- Do not commit credentials. If a full authenticated admin proof is needed, obtain passwords from the operator out of band and scrub logs.
- Authenticated Filament resources (contacts, AI import, finance) are out of scope for this starter map.
