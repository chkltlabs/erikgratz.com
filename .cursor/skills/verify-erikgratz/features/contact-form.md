# Contact form

Visitors send a name, contact handle, and message from `/contact`; submissions persist for admin review.

## Sub-features

- `contact-open` — contact form is reachable.
- `contact-submit` — valid submit shows success and stores a row.
- `contact-validation` — empty required fields stay on the page with errors (optional).

## How to get to it (user POV)

- Choose `Contact` in the header.
- Open `/contact`.

## Driving it with Chrome DevTools MCP

Preconditions:

- Doctor ok.
- Choose a unique `RUN_ID` marker (e.g. `verify-contact-20260906T184500Z`).

- **Open.** Navigate to `{BASE}/contact`. Form shows “Say Something Kind?” and fields `#contact`, `#name`, `#message`.
- **Fill.** Set contact to `verify@example.com`, name to `Verify Bot`, message to `Hello RUN_ID marker`.
- **Submit.** Click the form submit button. Success flash `contact request sent!` appears.
- **Side effect.** Run `bin/assert-contact.sh RUN_ID` — exit 0.
- **Proof.** Screenshot of success state + assert-contact stdout in `artifacts/contact-form/<run-id>/`.
- **Cleanup fixtures.** Run `bin/cleanup-contact.sh RUN_ID` after proof is saved (does not delete artifacts).

## Gotchas

- Submit button label is randomized (`Validate me...`, etc.); click the form’s submit control, do not hardcode the label.
- Livewire needs a real browser session (CSRF/cookies); bare curl POST is not a valid pass.
- Leave admin Contact resource browsing out of this feature unless credentials are provided separately.
