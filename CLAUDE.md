# shop.bazardev.com

Multi-seller e-commerce SaaS: sellers register, get a store (`{slug}.shop.bazardev.com` on paid plans,
`shop.bazardev.com/{slug}` path URL on Free), pick a storefront theme + product-page template, add
products with variants, and optionally build Facebook-ad-ready "landing pages" per product. Laravel 13,
PHP 8.3+, Breeze auth, Tailwind + Alpine.js, SQLite/MySQL, SSLCommerz for payments.

**Owner:** solo/small-team project, not yet in production — no real sellers to protect yet, but code
quality and docs are kept production-grade from day one.

## Source of truth: `docs/`

Every non-trivial feature here has a design doc in `docs/*.md`, written **before** building anything
risky or ambiguous — context/why, research findings (with "verified" vs "plausible but unverified"
flagged explicitly), data model, roadmap with ✅/⛔ status per milestone, and an honest "যা বাদ থাকলো"
(what's deliberately left out, and why) section. **Read the relevant doc before touching a feature
area** — it has the real constraints, not just the code. When you build something new or research a
new integration, write the doc in this same style (Bangla prose, English/code for technical bits) —
don't skip it because "the code is self-explanatory."

Existing docs (read before touching these areas):
- `docs/revenue-model.md` — Plan-based SaaS billing, why pure commission doesn't work here (platform
  never touches seller payment flows), admin-manual plan assignment (no self-serve billing yet)
- `docs/fcommerce-landing-pages.md` — landing page / one-page-funnel feature, FB Pixel+CAPI, SSLCommerz,
  explicitly why courier API (Steadfast/Pathao/RedX) was *not* built (no verified official docs found)
- `docs/product-variants-and-templates.md` — variant system + 8 product-detail templates, shared
  server-computed data layer (`$variantsData`/`$optionChoices`/`$available`) so templates never
  duplicate business logic
- `docs/product-page-completeness-plan.md` — specs/SKU/size-guide/reviews/Q&A/bundles, phased by risk

## Architecture facts that matter

- **Tenancy**: one route list (`$storefrontRoutes` closure in `routes/web.php`) registered twice — once
  under `Route::domain('{store}.'.config('shop.base_domain'))` for subdomain-plan stores, once under
  `Route::prefix('{store}')` on the main domain for Free-plan path URLs. Same controllers both ways —
  never duplicate storefront logic to special-case one URL style.
- **Plan gating is runtime, not hardcoded**: `plans` table is fully admin-editable (`/admin/plans`) —
  product/landing-page limits, allowed templates/themes (null = all), SSLCommerz/Pixel/subdomain
  toggles, branding removal. Enforce every gate server-side (`Plan::allowsTemplate()` etc.) even though
  the seller UI also greys out locked options — never trust the client-side lock alone.
- **Template/theme additivity**: storefront themes, product templates, and landing-page templates are
  all markup/CSS variations over one shared data layer. Adding a new one must never touch business
  logic — if it does, the data layer is wrong, not the template.
- **No customer accounts** — buyers aren't logged in. "Verified purchase" gates (reviews) use the
  order's own secret token, not auth. Keep this pattern for anything customer-facing.
- **Platform never holds money** — COD goes straight to seller, SSLCommerz uses the *seller's own*
  Store ID/Password. Don't design any feature that routes payment through a platform-owned account
  without flagging it as a deliberate, discussed scope change (regulatory/liability shift).
- **Custom domain mapping for stores is NOT built** — only a placeholder field exists. Don't assume it
  works. (`DomainCheck`/`DomainAvailability` is an unrelated lead-gen "is this domain name available"
  tool on the marketing site, not store custom-domain routing.)

## Conventions

- Run `php artisan test` before calling anything done — self-reported pass count is cited in commit
  messages and docs (e.g. "146/146 tests pass"), keep that habit.
- `Collection::map()` passes `($value, $key)` — `fn ($v) => trim($v)`, never bare `trim`, or the key
  silently becomes `trim()`'s character-mask argument. This bit the codebase twice already
  (`VariantGenerator`, landing-page benefits parsing).
- Cross-store/cross-product references (variant `image_id`, bundle `companion_id`) must be validated
  against the owning store's own records server-side — several existing features do this by intersecting
  against `$product->images()->pluck('id')` or equivalent; copy that pattern, don't trust client-sent IDs.
- Don't integrate a third-party API (courier, payment, etc.) against a guessed or community-reverse-
  engineered spec for anything that moves money or physical goods — get the real vendor docs first, or
  explicitly skip it and say why (see M5 in `docs/fcommerce-landing-pages.md` for the precedent).
- New DB fields should be nullable/default-safe — zero behavior change for existing rows is the default
  expectation, called out explicitly in nearly every doc above.
- User and assistant communicate in Banglish/Bangla; code, comments, and these docs stay mixed
  Bangla-prose/English-code as already established — don't switch the docs to pure English.
