---
name: verify-erikgratz
description: Drive the ErikGratz.com Laravel/Livewire public site (and Filament admin login surface) via Sail + browser/HTTP to prove user-facing behavior. Use when verifying UI routes, contact form, fit chat, nav, or admin login after changes.
---

# Verify ErikGratz.com

Agent-facing control skill for the **public Livewire marketing site** (primary) and the **Filament `/admin` login surface** (secondary). Read `features/` before driving. Prove real user paths; do not call Livewire internals or factory seeders as a substitute for the UI.

## Surfaces

| Surface | Stack | Base URL |
|---------|-------|----------|
| Public site | Livewire Volt pages | `http://127.0.0.1:${APP_PORT:-2080}` |
| Admin | Filament panel | same host + `/admin` |

There is no Playwright/Cypress suite in-repo. Drive the browser with the **Chrome DevTools MCP** (`user-chrome-devtools`: navigate, snapshot, click, fill, screenshot). Use the helpers below for HTTP doctor checks and contact side-effect queries.

## Launch

This app runs as a **shared Sail stack** (one MySQL + one `laravel.test`). Do **not** start a second Compose project against the same ports.

1. From the repo root, if doctor fails:

```bash
./vendor/bin/sail up -d
```

2. Wait until ready:

```bash
./.cursor/skills/verify-erikgratz/bin/doctor.sh
```

Ready = exit `0` and `/home` returns HTTP 200 with body containing `Erik`.

Teardown of a stack **you** started in this verification run:

```bash
./vendor/bin/sail down
```

Do not `sail down` if the stack was already running before the verification run (shared developer instance). Record whether you started it; only tear down what you started.

Default host port comes from `.env` `APP_PORT` (example/default **2080** → container `:80`). Confirm with `./vendor/bin/sail ps`.

## Doctor

Always run before driving when anything looks off:

```bash
./.cursor/skills/verify-erikgratz/bin/doctor.sh
```

Checks: Compose `laravel.test` is up, `APP_PORT` answers, `GET /home` is 200 and contains a public-site marker. Exit non-zero ⇒ stop; fix launch before driving.

Optional admin probe (login page only, no credentials):

```bash
./.cursor/skills/verify-erikgratz/bin/doctor.sh --admin
```

## Drive

1. Run doctor.
2. Open a **new** Chrome DevTools page (do not hijack the user's everyday tab if avoidable): MCP `new_page` or `navigate_page` to `BASE` from doctor output.
3. Prefer:
   - Route paths from `docs/routes-and-urls.md` (`/home`, `/experience`, `/contact`, `/fit`, `/admin/login`)
   - Nav link text: `Home`, `Work`, `Experience`, `Fit`, `Photo`, `Portfolio`, `Contact`
   - Form fields by `id` / label: contact `#contact`, `#name`, `#message`; fit `#question`, `#jobDescription`
4. Take an accessibility **snapshot** before clicking ambiguous controls.
5. Follow the matching file under `features/`.

HTTP-only smoke (no browser) is allowed for read-only pages via:

```bash
./.cursor/skills/verify-erikgratz/bin/http-get.sh /experience
```

Mutations (contact submit, fit ask) require the browser path so Livewire/CSRF behave like a user.

### Fit chat

Public fit uses the Laravel AI SDK (Gemini by default). Browser verification needs `GEMINI_API_KEY` (or `AI_API_KEY`). Without a key, a safe error banner is a valid failure proof, not a pass. PHPUnit fakes (`PublicFitAgent::fake`) do not apply to the live site.

### Admin

Full Filament resource proofs need credentials from the operator's `.env` (`MASTER_PASSWORD` / seeded users). This skill's default map only proves the **login page loads**. Never embed passwords in the skill or artifacts.

## Evidence

Store proofs under:

```text
.cursor/skills/verify-erikgratz/artifacts/<feature-id>/<run-id>/
```

`run-id` = UTC timestamp like `20260906T184500Z`.

Required for a pass:

- The user action (URL navigated, form values submitted, button clicked)
- Resulting UI state: write MCP `take_snapshot` text to `*.aria.txt`; keep an MCP screenshot in the agent turn (Chrome DevTools MCP may refuse `filePath` writes into this repo — do not fail the run solely for that; persist the snapshot text and HTTP helper output into `artifacts/`)
- Side effects when applicable (e.g. contact row via `bin/assert-contact.sh`)
- Feature id + entry point recorded in `meta.txt`

Proof standards:

- Exercise the real route/UI, not PHPUnit Livewire::test alone
- Capture before/after or action + resulting state
- Do not invent app-level `AI_FAKE` shortcuts for browser runs — use a real key or record the config failure

## Cleanup

- Close browser pages opened for the run (MCP `close_page`)
- Delete verification-only DB rows tagged in the feature recipe (contact messages containing the run-id marker)
- If **you** started Sail for this run: `./vendor/bin/sail down`
- **Never delete** `artifacts/` proof files

## Helpers

All scripts are executable; invoke from repo root.

| Script | Purpose |
|--------|---------|
| `bin/doctor.sh` | Read-only health of public site (+ optional `--admin`) |
| `bin/base-url.sh` | Prints `http://127.0.0.1:$APP_PORT` |
| `bin/http-get.sh <path>` | GET path; prints status + body snippet; exit 0 on 2xx |
| `bin/assert-contact.sh <marker>` | Asserts a `contacts` row whose `message` contains `<marker>` |
| `bin/cleanup-contact.sh <marker>` | Deletes contact rows matching that marker |

Example:

```bash
./.cursor/skills/verify-erikgratz/bin/doctor.sh
./.cursor/skills/verify-erikgratz/bin/http-get.sh /home
```

## Feature map

See [features/README.md](features/README.md).
