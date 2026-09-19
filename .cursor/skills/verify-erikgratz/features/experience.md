# Experience page

Experience lists Erik’s professional roles (company, title, timeframe, bullets, technologies) for public visitors.

## Sub-features

- `experience-route` — `/experience` returns the Experience page.
- `experience-roles` — at least one known employer (e.g. Pocketnest) is visible.

## How to get to it (user POV)

- Choose `Experience` in the header nav.
- Open `/experience` directly.

## Driving it with Chrome DevTools MCP

Preconditions:

- Doctor ok.

- **Open.** Navigate to `{BASE}/experience` or click nav `Experience`. Heading/context indicates Experience.
- **Content.** Snapshot shows `Pocketnest` (or another current role from the page). Optional HTTP: `bin/http-get.sh /experience` and grep the body for `Pocketnest`.
- **Proof.** `experience.png` + `experience.txt` under `artifacts/experience/<run-id>/` with meta noting entry point.

## Gotchas

- Role titles on Home vs Experience can differ slightly; assert employer names, not exact title strings across pages.
- Do not treat Filament `Activity` “experience” spend types as this page.
