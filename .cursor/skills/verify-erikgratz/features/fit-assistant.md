# Career fit assistant

`/fit` lets visitors ask about Erik’s work history or paste a job description for a fit assessment using the public knowledge pack.

## Sub-features

- `fit-open` — page renders the Family A editorial UI.
- `fit-ask` — submitting a question shows one answer card (or a safe error banner).
- `fit-clear` — Clear resets the form and answer.

## How to get to it (user POV)

- Choose `Fit` in the header.
- Open `/fit`.

## Driving it with Chrome DevTools MCP

Preconditions:

- Doctor ok.
- Prefer a configured `GEMINI_API_KEY` (or `AI_API_KEY`) for a full pass. PHPUnit uses `Agent::fake()` / `Embeddings::fake()` — those do not apply to the live browser.

- **Open.** Navigate to `{BASE}/fit`. Heading `Fit` (`text-4xl` purple) is visible; `#question` and optional `#jobDescription` exist. No chat bubbles / “Thinking…” copy.
- **Ask.** Fill `#question` with `What did you build at Pocketnest?` and submit `Ask`. Within a few seconds a gray-800 answer card appears under the form (or a red error for validation/rate limit — never a raw provider body).
- **Pass criteria.** With a working API key, answer text references work history (e.g. Pocketnest/Laravel). Without a key, an explicit safe error banner is a **known-fail** for AI config, not a UI routing pass — still capture it.
- **Proof.** Screenshot of answer card + `meta.txt` noting whether `GEMINI_API_KEY` was set under `artifacts/fit-assistant/<run-id>/`.

## Gotchas

- Page GET is throttled; asks use the app rate limiter (`CHATBOT_PUBLIC_RATE_LIMIT`).
- Assessments are single-shot to the model (no multi-turn history).
- Do not treat PHPUnit `DualChatbotTest` alone as UI verification for this feature.
