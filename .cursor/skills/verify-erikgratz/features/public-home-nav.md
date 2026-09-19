# Public home and navigation

The public site lands on Home and exposes primary section links in the header so a visitor can move between marketing pages without knowing URLs.

## Sub-features

- `home-redirect` — `/` redirects to `/home`.
- `home-render` — `/home` shows Erik identity content.
- `nav-links` — header lists Home, Work, Experience, Fit, Photo, Portfolio, Contact.

## How to get to it (user POV)

- Open `/` or `/home` in the browser.
- Use header nav links by their visible titles.

## Driving it with Chrome DevTools MCP

Preconditions:

- `bin/doctor.sh` exits 0.
- Base URL from `bin/base-url.sh`.

- **Redirect.** Navigate to `{BASE}/`. Run MCP `navigate_page` to `{BASE}/`. Final URL is `{BASE}/home` (or follows redirect) and page shows Home content.
- **Home content.** Navigate to `{BASE}/home`. Snapshot/screenshot shows page identity (name/hero related to Erik). Optional: `bin/http-get.sh /home` returns 200.
- **Nav.** From Home, click the `Experience` nav link (text `Experience`). URL becomes `{BASE}/experience`.
- **Proof.** Save screenshot `home.png`, snapshot `home.aria.txt`, and `meta.txt` listing entry `/home` under `artifacts/public-home-nav/<run-id>/`.

## Gotchas

- Mobile widths hide nav behind a hamburger (`Menu`); resize the viewport to at least **768px** wide (MCP `resize_page`) or open the menu before asserting header links.
- Logo SVG links to `/admin/login` — that is not the Home entry point.
