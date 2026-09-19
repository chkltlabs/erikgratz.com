# ErikGratz.com verification map

Maintained source for verifying user-facing behavior of erikgratz.com. Read this index, then the matching feature file.

## Baseline preconditions

- Sail stack healthy: `./.cursor/skills/verify-erikgratz/bin/doctor.sh` exits 0.
- Base URL from `./.cursor/skills/verify-erikgratz/bin/base-url.sh` (typically `http://127.0.0.1:2080`).
- Drive with Chrome DevTools MCP unless the feature allows `http-get.sh` for read-only checks.
- This environment is a **shared** Sail instance (one MySQL). Do not start a second compose project on the same ports. Prefer unique run-id markers for any writes; clean those rows after proof.
- Never drive a host that doctor rejects.

## Driving conventions

- Start from `/home` unless the feature says otherwise.
- Prefer route paths and visible nav link text (`Home`, `Experience`, `Fit`, `Contact`, …) over CSS position.
- Pair every user action with an MCP or helper command and an observable result.
- Keep proof under `.cursor/skills/verify-erikgratz/artifacts/<feature-id>/<run-id>/`.

## Proof and skip reporting

- Capture action + resulting state (screenshot + text/ARIA snapshot).
- Mutation proof includes a second observation (DB assert or reload).
- Record feature id and entry point in `meta.txt`.
- An unreachable path is a fail with the unmet precondition — do not claim pass via a different entry.

## Feature entry contract

Each feature file: H1 + one paragraph, then exactly these H2s in order: `Sub-features`, `How to get to it (user POV)`, `Driving it with Chrome DevTools MCP`, `Gotchas`.

## Features

- [Public home and navigation](./public-home-nav.md) — landing redirect, home content, header nav.
- [Experience page](./experience.md) — public work-history page content.
- [Contact form](./contact-form.md) — submit a contact request and confirm persistence.
- [Career fit assistant](./fit-assistant.md) — `/fit` Q&A UI (needs `GEMINI_API_KEY` for a full pass).
- [Admin login surface](./admin-login.md) — Filament login page loads (no password in proofs).
