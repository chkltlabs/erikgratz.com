# Travel-wallet benefit catalog

`description` is human notes only. Ranking never parses it. Encode every restriction in columns.

Seed and edit [`database/data/held-card-benefits.json`](../database/data/held-card-benefits.json). [`HeldCardBenefitsSeeder`](../database/seeders/HeldCardBenefitsSeeder.php) upserts onto held cards. Related catalogs: [`local-cards.json`](../database/data/local-cards.json), [`loyalty-programs.json`](../database/data/loyalty-programs.json), [`loyalty-program-perks.json`](../database/data/loyalty-program-perks.json), [`transfer-routes.json`](../database/data/transfer-routes.json).

Airline programs in `loyalty-programs.json` cover the top 60% (ranks 1–60) of the [StatRanker 2026 passengers-carried snapshot](https://statranker.org/mobility/top-100-airlines-by-passengers-carried-2026-snapshot/) when the carrier has a frequent-flyer program. Group brands that share one program (Miles & More, Flying Blue, Qantas, Alaska, KrisFlyer) are one row plus `VendorMatcher` aliases. Ryanair, easyJet, Wizz Air, Volaris, Viva Aerobus, TUI, and Jet2 are omitted — paid clubs or leisure brands, not mileage programs.

## Product keys

Top-level JSON keys are product nicknames matched from `cards.name` (substring, case-insensitive):

| Key | Card name contains |
|-----|--------------------|
| `csr` | Sapphire Reserve |
| `csp` | Sapphire Preferred |
| `vx` | Venture X or C1 V X |
| `plat` | Amex Plat |
| `green` | Amex Green |
| `aerlingus` | Aer Lingus |
| `aeroplan` | Aeroplan |
| `ba` | Chase BA or British Airways |

Benefit upsert key is `(card_id, benefit)` — renaming a benefit creates a new row and leaves the old one. Membership `code` must exist in `loyalty-programs.json`.

Each product object may have `memberships`, `benefits`, `rates`, `card_perks`. Nested `perks` on a benefit attach to that benefit; `card_perks` attach to the card. A perk has exactly one owner.

`next_refresh_at` is computed by [`BenefitRefresher`](../app/Services/TravelWallet/BenefitRefresher.php) after seed; do not hand-author it.

## How to encode a new or changed benefit

Work official terms top-down. Stop at the first row that fits.

1. **Not travel for Book Travel** (dining, rideshare, memberships, airline *incidentals*) → `applies_to: other`. Book Travel will not spend it on a fare.
2. **Status / companion / lounge flag** → `value_kind: flag`, `tracking_mode: ignore`. Ranking ignores flags.
3. **Must book through a portal or direct** → `required_channel` (`direct`, `chase_travel`, `amex_travel`, `capital_one_travel`).
4. **Only some airlines / hotel brands** → `allowed_vendors` (lowercase needles). Empty/`null` = any vendor. Blank trip vendor fails a restricted credit.
5. **Award taxes/fees only, not cash airfare** → `award_only: true` (cash combos skip it; award `quoteCash` can still apply).
6. **N redemptions per window, each capped** → `quantity_total: N` plus `max_apply_per_use`. Cabin map for BA-style; `{ "default": dollars }` for a flat per-stay cap (The Edit).
7. **One pool that can land on a single booking** (CSR $300 travel, Plat FHR $300) → leave `quantity_total` and `max_apply_per_use` unset. `value` is the period pool.
8. **City/country scoped** → `location_city` / `location_country`. Blank trip location fails a scoped credit.
9. **Conferred by a loyalty program** → `program` (seed-only; seeder writes `loyalty_membership_id`).
10. Write the official wording in `description` for humans. Never rely on it for ranking.

Stay-length, prepaid, and “activation required” stay in `description` until we add those columns.

## Benefit columns

| Key | Required | Ranking | What to put |
|-----|----------|---------|-------------|
| `benefit` | yes | display / upsert key | Stable title. Renaming forks the row. |
| `description` | no | ignored | Official terms, in plain language. |
| `value` | currency | dollar pool (`remaining()`) | Period max in dollars. |
| `value_kind` | yes | pool shape | `currency` / `quantity` / `flag`. |
| `quantity_total` | no | redemption count when set | Max uses in the current window. Null = no use-count cap. For `value_kind: quantity` this *is* the allotment. |
| `tracking_mode` | yes | expendable? | `track` (manual), `auto` (statement credit; standing assume each period), `ignore` (not spendable, never assumed). Edit Card → Benefits, Benefits Due, and Assumed captured hide ignored by default; **All** lists them last and greyed. |
| `auto_assume_amount` | no | Auto only | Standing amount assumed each closed window. Null = full allotment. Ignore (or 0) writes no usage, so it does not add to Edit Card **Benefit value this year**. |
| `reset_period` | no | window | `no_reset`, `daily`, `weekly`, `monthly`, `quarterly`, `semi_annual`, `calendar_yearly`, `renewal_yearly`. |
| `reset_anchor` | no | window start | `calendar`, `statement`, `card_anniversary`, `custom`. |
| `custom_reset_on` | if custom | window | Date. |
| `applies_to` | yes | category filter | `any`, `flight`, `hotel`, `car`, `other`. `other` never matches a Book Travel category. |
| `required_channel` | no | channel filter | Null = any channel. |
| `location_country` / `location_city` | no | location filter | Substring match on trip location. |
| `program` | no | seed-only | Loyalty code → `loyalty_membership_id`. |
| `is_useable` / `is_used` | yes | UI leftovers | Keep `is_useable: true` for live benefits. |
| `allowed_vendors` | no | vendor allowlist | JSON string list. Null/empty = unrestricted. |
| `max_apply_per_use` | no | per-redemption $ cap | `{ "default": N }` and/or cabin keys `economy`, `premium_economy`, `business`, `first`. Null = no per-use $ cap. |
| `award_only` | no | cash vs award | Default false. True = omit from cash combos. |
| `perks` | no | combo perks | Nested booking perks (see below). |

## Lists and allowed values

**`allowed_vendors` needles** — lowercase; [`VendorMatcher`](../app/Services/TravelWallet/VendorMatcher.php) substring-matches the trip vendor (and existing loyalty aliases). Prefer the public brand name plus short aliases already used in rates (`air canada`, `ba`, `ihg`). Add a needle here *and* a `VendorMatcher` alias when the brand has many trade names (for example `holiday inn` → `ihg`).

**`max_apply_per_use` keys** — `default` plus [`BookingCabin`](../app/Enums/BookingCabin.php) values. Lookup is `map[cabin] ?? map.default ?? null`.

**Memberships** — `{ "code": "<loyalty-programs.json code>", "tier": "Platinum Elite" | null, "number": "123456789" }`. `number` is optional and becomes `loyalty_number`. Each row is a household person (`user_id` on `loyalty_memberships`), not the Filament login. Amy and Erik can both hold Aeroplan; unique is `(user_id, loyalty_program_id)`. The seeder keys on the card's cardholder + program. Book Travel auto-attaches only when exactly one membership matches the vendor; two people on the same program shows a Member · Program · tier picker.

Memberships are not login-scoped — one admin manages the whole household.

**Assumed captured** (`tracking_mode: auto`) on Benefits Due: Use fully / Use partial / Ignore is the standing rule for every period until you change it. **Benefit value this year** on Edit Card only sums `benefit_usages` in the card membership year.

Membership `points_balance` feeds the dashboard Points widget together with card `points_balance`. Card and loyalty amounts are shown separately on each stat. Avios-family memberships (`ba`, `qatar`, `aerlingus`, `iberia`, `finnair` in `travel-wallet.avios_program_codes`) plus cards with `points_program: avios` display as one Avios total. Book Travel still treats those programs as separate accounts. Do not store the same Avios pot on both a cobrand card and a membership — the widget adds them.

**Rates** — `{ "category": flight\|hotel\|car, "channel": <BookingChannel>, "multiplier": number, "vendor": null | needle }`. Null/`""` `vendor` = any vendor on that channel. A filled `vendor` wins over the empty-vendor row on the same channel (Plat: 5x `direct` for airlines, 1x `direct` + `expedia` / `priceline`). Unmatched channels fall back to `travel-wallet.default_earning_rate` (1x).

**Perks** (`perks` / `card_perks`) — `name` (upsert key), `description`, `decision_value` (dollar-equivalent score), `applies_to`, `channel` (nullable), `award_only`. Card-level perks may omit `channel`.

## Worked examples

- **BA $600** — flight + `allowed_vendors` + `quantity_total: 3` + cabin map + `award_only`.
- **The Edit $500** — hotel + `required_channel: chase_travel` + `quantity_total: 2` + `max_apply_per_use.default: 250`.
- **CSR $250 select hotels** — hotel + channel + `allowed_vendors` only (`value` already equals one use).
- **Aeroplan $50** — flight + `required_channel: direct` + `allowed_vendors`.
- **CSR $300 travel** — `applies_to: any`, no vendor/use/cap fields.
- **Plat $200 airline fee** — `applies_to: other` (not airfare).
- **Plat flight earn** — 5x `amex_travel` and 5x `direct`; 1x `direct` when the trip vendor is Expedia or Priceline.
