# Custom Domain Mapping

**Status:** M1 + M2 done (2026-11-19, 160/160 tests pass + live infra-level verification). M3 (a
real domain's full cert-issuance test) still needs an actual domain - see §7.
**Owner context:** shop.bazardev.com (multi-seller marketplace)
**Date:** 2026-11-19

## ১. প্রসঙ্গ (Why)

এখন একটা store দুইভাবে পাওয়া যায়: `shop.bazardev.com/{slug}` (Free) বা `{slug}.shop.bazardev.com`
(Subdomain/Pro plan)। Settings পেজে একটা "custom domain — coming soon" placeholder field আছে, কিন্তু
কোনো backend কাজ হয়নি। সেলার-রা যারা নিজের ব্র্যান্ডেড ডোমেইন (যেমন `mydress.com`) ব্যবহার করতে চায়,
তাদের জন্য এটা দরকার — আর platform-এর যেকোনো storefront route (home, `/p/{slug}` product detail,
`/lp/{slug}` landing page, cart, checkout, order-status) সেই custom domain-এও কাজ করা লাগবে, সাবডোমেইনের
মতোই — product detail page বাদ পড়লে এটা অসম্পূর্ণ ফিচার হবে।

## ২. গুরুত্বপূর্ণ আবিষ্কার — Caddy on-demand TLS ask-chain আসলে cross-project

সার্ভার-লেভেল config পড়ে কনফার্ম করা হলো (অনুমান না):

- `/etc/caddy/Caddyfile`-এ গ্লোবাল `on_demand_tls { ask http://127.0.0.1:8099/internal/domain-check }`
  — **পুরো সার্ভারের জন্য এটা একটাই ask endpoint**, Caddy-তে এই ব্লক একবারই define করা যায়।
- পোর্ট **৮০৯৯ shop-এর নয় — portfolio.bazardev.com অ্যাপ owns করে**
  (`/etc/caddy/sites/portfolio.caddy`-তে `http://127.0.0.1:8099` bind করা)।
- portfolio-এর `routes/web.php`-এর `/internal/domain-check` রুট নিজের username-ভিত্তিক ডোমেইন চেক করে,
  **আর যদি domain `shop.bazardev.com` বা `*.shop.bazardev.com` হয়, সেটা shop-এর নিজের
  `http://127.0.0.1:8098/internal/domain-check`-কে forward করে** (shop-এর `routes/web.php`-এ এই
  endpoint-টা আগে থেকেই আছে, এখন শুধু `.shop.bazardev.com` pattern আর DB-তে published store চেক করে)।

**এর মানে:** একটা seller-এর custom domain (যেমন `mydress.com`)-এর জন্য TLS সার্টিফিকেট ইস্যু হওয়ার আগে
Caddy portfolio-এর ask endpoint-কে হিট করবে — কিন্তু portfolio-এর বর্তমান কোড শুধু তার নিজের ডোমেইন আর
`*.shop.bazardev.com` pattern চেনে, `mydress.com`-এর মতো arbitrary domain পেলে সরাসরি `abort(404)` করে
দেবে, shop-কে জিজ্ঞেসই করবে না। **তাই এই ফিচারের জন্য portfolio.bazardev.com-এর
`routes/web.php`-তেও একটা ছোট পরিবর্তন লাগবে** (recognized pattern না-মিললে shop-কে fallback-forward
করা) — এটা একটা আলাদা, live project, তাই এই পরিবর্তন আলাদাভাবে দেখানো ও approve করানো হবে, শুধু shop-এর
কাজের ভেতরে চুপচাপ ঢুকিয়ে দেওয়া হবে না।

## ৩. Data model plan

নতুন migration (কোনো existing টেবিল ভাঙবে না):

```php
// store_domains
$table->id();
$table->foreignId('store_id')->constrained()->cascadeOnDelete();
$table->string('domain', 255)->unique(); // "mydress.com", lowercase, no scheme/path
$table->string('verification_token', 40);
$table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
$table->timestamp('verified_at')->nullable();
$table->timestamp('last_checked_at')->nullable();
$table->timestamps();
$table->index('status');
```

একটা store-এর একাধিক custom domain থাকতে পারবে (future-proof), কিন্তু শুধু `verified` স্ট্যাটাসের
ডোমেইনই route করবে। `Settings`-এর বর্তমান placeholder field বাদ দিয়ে এই নতুন টেবিলে সরানো হবে।

## ৪. Verification flow — কেন TXT record, আর কেন লাগবে

Domain ownership verify করা দরকার, নাহলে একজন সেলার আরেকজনের আসল ডোমেইনের নাম টাইপ করে দিলেও সিস্টেম
সেটা store-এর সাথে associate করে ফেলবে — DNS আমাদের দিকে পয়েন্ট না করলে TLS/ট্রাফিক কখনো আসবে না ঠিকই,
কিন্তু ownership proof ছাড়া রুট করলে ভুল/বিভ্রান্তিকর claim ডেটাবেজে থেকে যায়, আর ভবিষ্যতে একই ডোমেইন
অন্য কেউ claim করতে চাইলে race condition তৈরি হতে পারে।

- Seller domain add করলে একটা random `verification_token` জেনারেট হয়; UI-তে দেখানো হয় একটা TXT record
  বসানোর নির্দেশ: `_bazardev-verify.mydress.com TXT "token-value"`।
- সেলার নিজের DNS provider-এ TXT বসিয়ে "Verify" চাপলে একটা artisan command/job DNS query করে
  (`dns_get_record($domain, DNS_TXT)`) — মিললে `status = verified`, `verified_at` সেট হয়।
- Verify না হওয়া পর্যন্ত domain কোনো ট্রাফিক route করে না (শুধু `pending` অবস্থায় থাকে, সেলার UI-তে
  "DNS record বসান, propagate হতে কয়েক ঘণ্টা লাগতে পারে" মেসেজ দেখে)।
- এছাড়াও সেলারকে একটা A record নির্দেশও দেওয়া হবে (সার্ভারের পাবলিক IP-তে পয়েন্ট করতে) — এটাই আসল ট্রাফিক
  routing করে, TXT কেবল ownership proof।

## ৫. Technical architecture

**Tenant resolution (`ResolveStoreFromHost` আপডেট):** এখন host হয় base domain, নয় `{slug}.base_domain`
— এই লজিকের একটা তৃতীয় শাখা লাগবে: host যদি কোনোটাই না মেলে, `store_domains` টেবিলে
(`domain = host AND status = 'verified'`) lookup করে store বের করা। Middleware-এর নামও honestly
`ResolveStoreFromHost` থেকে বদলাবে না (এখনও host-ই resolve করছে), শুধু lookup branch বাড়বে।

**Route registration:** বর্তমান `$storefrontRoutes` closure দুইবার register হয় (subdomain + path)।
কাস্টম ডোমেইনের জন্য এটা **তিনবার register করার দরকার নেই** — Laravel route matching নির্দিষ্ট domain
string চায়, কিন্তু runtime-এ unknown domain-এর জন্য ওয়াইল্ডকার্ড domain route বানানো সম্ভব না (domain
ডাটাবেজ থেকে আসে)। সমাধান: Caddy-তে কাস্টম ডোমেইনগুলোর জন্য একটা catch-all site block
(`*.* { reverse_proxy shop-app }` ধরনের না — বরং প্রতিটা verified domain-এর জন্য Caddy-কে
*dynamically* জানাতে হবে এটা shop-এর app-এ যাবে)। এখানে দুইটা বাস্তবসম্মত পথ:

| পথ | কীভাবে | Trade-off |
|---|---|---|
| **A. একটা catch-all Caddy site + Laravel-এ host দিয়ে resolve** | Caddy-তে একটাই block `$storefrontRoutes`-এর জন্য, কিন্তু domain হার্ডকোড না করে Caddy-র fallback/default site হিসেবে — যেকোনো domain যেটা আগের নির্দিষ্ট site-ব্লকগুলোর কোনোটায় ম্যাচ করে না, সেটা shop-এর PHP-FPM-এ পাঠানো, PHP-সাইডেই `store_domains` lookup করে বের করা কোন store | সবচেয়ে কম Caddy-config-change, কিন্তু "catch-all" block সার্ভারের অন্য অব্যবহৃত/ভুল ডোমেইনের ট্রাফিকও শপ-এ পাঠাবে — PHP-সাইডে lookup miss হলে clean 404 দিতেই হবে |
| **B. প্রতিটা verified domain-এর জন্য আলাদা Caddy site ব্লক generate** | সেলার verify করলেই একটা নতুন `.caddy` ফাইল লেখা + Caddy reload (admin API বা `caddy reload`) | বেশি সুনির্দিষ্ট, কিন্তু প্রতি domain-এ একটা reload — এই প্রজেক্টের অন্য কোথাও dynamic Caddy config/admin-API avoid করা হয়েছে (terminal.bazardev.com-এর orchestrator ঠিক এই কারণে static Caddy রেখেছিল) |

**সুপারিশ: Path A** — এই প্রজেক্টের established pattern-এর সাথে সবচেয়ে বেশি মেলে (static Caddy config,
app-সাইডে routing decision)। shop.caddy-তে একটা catch-all domain block যুক্ত হবে যা shop-এর নির্দিষ্ট
domain-গুলো (base + wildcard subdomain) ছাড়া বাকি সবকিছুর জন্য fallback হবে — কিন্তু **এই catch-all
Caddy-র global config-এ বসবে, তাই অন্য কোনো সাইটের সাথে conflict না করে এটা ঠিক কোথায় বসবে, Caddy-র
site-matching priority (সবচেয়ে specific host আগে ম্যাচ করে) ভালোভাবে verify করতে হবে বাস্তব টেস্টে।**

**on-demand TLS ask (shop-এর `/internal/domain-check`):** বর্তমান pattern-check-এর পাশে একটা নতুন branch
— pattern না মিললে `store_domains`-এ verified lookup, মিললে `ok`।

**product/landing route parity:** যেহেতু catch-all route-ও একই `$storefrontRoutes` closure চালাবে,
`/p/{slug}`, `/lp/{slug}`, cart, checkout — সবকিছু automatically কাজ করবে custom domain-এও, কোনো আলাদা
কোড লাগবে না (এই কারণেই এই প্ল্যানে domain-resolution-টাই মূল কাজ, routes নিজে অপরিবর্তিত থাকে)।

## ৬. Seller-facing UI

Settings পেজে বর্তমান placeholder field সরিয়ে একটা নতুন ছোট sub-section/পেজ:
- Domain add ফর্ম (একটাই input: domain name)
- Status badge (Pending verification / Verified / Failed) + TXT+A record নির্দেশ (pending অবস্থায়)
- "Re-check" বাটন (manual re-trigger DNS lookup)
- Remove বাটন

**Plan gate:** কাস্টম ডোমেইন Pro-only (subdomain-এর মতোই `allow_subdomain`-এর পাশে নতুন
`plans.allow_custom_domain` কলাম, Free/Subdomain plan-এ off, Pro-এ on, admin editable)।

## ৭. Roadmap

1. ✅ **M1 — ডেটা + verification (done, 2026-11-19):** `store_domains` migration
   (`domain` unique, `verification_token`, `status`, `verified_at`, `last_checked_at`),
   `plans.allow_custom_domain` column (Pro = true by default, migrated existing Pro plan),
   `App\Services\DomainVerifier` (random token + `dns_get_record` TXT lookup, DNS failures treated as
   "not verified yet" not an error), `Seller\CustomDomainController` (add/recheck/remove, one domain
   per store in V1, reserved-domain + duplicate-domain + cross-store-ownership checks all
   server-side), seller UI at `seller/custom-domain` (TXT+A record instructions, status badge,
   re-check button), Settings/admin-plan-form placeholders replaced with the real feature, admin
   plans list shows the new column. `config('shop.server_ip')` added so the UI can show a real A
   record target. **8 new tests, 154/154 total pass.** Routing is explicitly *not* wired yet - a
   verified domain does not yet serve the storefront (the UI says so).
2. ✅ **M2 — Routing (done, 2026-11-19):**
   - নতুন `App\Http\Middleware\ResolveStoreFromCustomDomain` + `store.custom-domain` alias,
     route-model-binding priority list-এও যুক্ত হলো (অন্য দুই resolver-এর মতোই)।
   - নতুন catch-all route: `Route::domain('{customDomain}')->where([...])->group($storefrontRoutes)`,
     `{store}.base_domain` group-এর ঠিক পরে এবং **সব domain-less main-domain route-এর আগে** বসানো হলো
     — কারণ ওই route-গুলো (home, search, admin, seller dashboard, profile, auth, এমনকি শেষের
     path-based `{store}` fallback-ও) কোনোটাই domain-scoped না, তাই আগে থাকলে তারাই custom-domain
     request "চুরি" করে ফেলতো। `where()`-এ negative-lookahead regex দিয়ে base domain ও তার
     subdomain explicitly বাদ দেওয়া হলো, যাতে অন্য দুই group-কে কখনো shadow না করে। **এটা শুধু যুক্তিতেই
     না, বাস্তব টেস্টেও verify করা হলো** — বিভিন্ন Host হেডার দিয়ে real HTTP request পাঠিয়ে (৬টা নতুন
     টেস্ট): base domain/subdomain/path URL অক্ষত, unverified/unknown domain 404, verified domain-এ
     store home + `/p/{slug}` কাজ করে, আর plan downgrade হলে domain সাথে সাথেই বন্ধ হয়ে যায়।
   - shop-এর `/internal/domain-check`-এ নতুন branch: pattern না মিললে `store_domains`-এ verified
     lookup।
   - **portfolio.bazardev.com-এর routes/web.php** (cross-project, আগে থেকেই অনুমতি নেওয়া ছিল) —
     ask-endpoint এখন নিজের pattern না মিললে shop-কে fallback-forward করে, শুধু `.shop.bazardev.com`
     pattern-এই সীমাবদ্ধ না। portfolio-র নিজের ৬টা নতুন টেস্ট দিয়ে verify করা হলো (own domain, username
     subdomain, shop-pattern forward, unknown-domain fallback forward, double-reject case) —
     portfolio-র pre-existing ১৪টা auth/CSRF টেস্ট ফেইলিউর (git stash করে কনফার্ম করা হলো, এই কাজের
     আগে থেকেই ছিল, আমার change-এর কারণে না) **touch করা হয়নি, fix করাও হয়নি — scope-এর বাইরে।**
   - **Caddy** (`/etc/caddy/sites/shop-custom-domains.caddy`, নতুন ফাইল) — একটা hostless `:443`
     catch-all server block, `tls { on_demand }`, shop-এর app-কেই point করে। Caddyfile backup নিয়ে,
     `caddy validate` দিয়ে syntax check করে, `systemctl reload caddy`-তে zero-downtime apply করা
     হলো। **যাচাই করা হলো:** ১১+টা অন্য live সাইট (bazardev.com, learn, terminal, portfolio, pos1,
     inventory1, trading, training, courier1, nirjhor-test, আর শপ নিজেই) সব আগের মতোই respond করছে;
     real published Pro store `dbg2.shop.bazardev.com`-এর subdomain আগের মতোই কাজ করছে (200); একটা
     fake/unclaimed test domain-এ TLS handshake নিজেই reject হয় (`curl --resolve` দিয়ে, real DNS/ACME
     ছাড়াই) — পুরো ask-chain (Caddy → portfolio:8099 → shop:8098 → `store_domains` lookup) বাস্তবে
     কাজ করে এটা প্রমাণ করে, Let's Encrypt-কে touch না করেই।
3. ⛔ **M3 — আসল ডোমেইন দিয়ে পুরো টেস্ট (not started, needs owner input):** M2-এর "deny" path বাস্তবে
   টেস্ট করা হয়েছে (উপরে), কিন্তু "accept" path-এর পুরো প্রমাণ (আসলেই Let's Encrypt থেকে একটা cert ইস্যু
   হওয়া) একটা real domain লাগবে, যার DNS genuinely আমাদের সার্ভারে পয়েন্ট করা — এটা fabricate করা যায় না
   (fake domain দিয়ে চেষ্টা করলে Let's Encrypt validation ব্যর্থ হবে এবং অকারণে retry/rate-limit হিট
   হতে পারে, তাই ইচ্ছাকৃতভাবে করা হয়নি)। owner-এর একটা real (বা disposable test) ডোমেইন থাকলে সেটা দিয়ে
   শেষ ধাপ verify করা যাবে।

## ৮. Risk ও Open questions

- **Cross-project change (portfolio.bazardev.com)** — এই একটা জায়গায় shop-এর কাজ আরেকটা live app-কে
  touch করে। M2-এ গিয়ে এই পরিবর্তনটা আলাদাভাবে দেখানো হবে, শুধু shop-এর commit-এর ভেতর চুপচাপ করা হবে না।
- **Caddy catch-all site-matching priority** — ডকুমেন্টেশনে specific-host-আগে নিয়ম থাকলেও, বাস্তব সার্ভারে
  ২০+ অন্য সাইট থাকার মধ্যে নতুন catch-all block যুক্ত করার পর কোনো existing site ভুলভাবে catch-all-এ পড়ছে
  কিনা — এটা বাস্তব টেস্টে (M3) verify করা must, শুধু ডক পড়ে ধরে নেওয়া যাবে না।
- **DNS verification-এর জন্য queue/cron লাগবে** — TXT propagate হতে সময় লাগে, তাই "Verify" বাটনে
  synchronous DNS lookup যথেষ্ট (manual re-check), কিন্তু চাইলে ভবিষ্যতে একটা scheduled job দিয়ে pending
  domain-গুলো periodically auto-recheck করা যায় (V1-এ স্কোপে নেই, manual-only)।
- **Let's Encrypt rate limits** — ask endpoint ভুল করে "ok" দিলে Caddy বারবার cert request করবে এবং
  domain-ভিত্তিক Let's Encrypt rate limit হিট হতে পারে; তাই ask endpoint-এর logic-এ কোনো bug/false-positive
  থাকা চলবে না — M3-এ staging/test domain দিয়েই যাচাই করা, production domain দিয়ে প্রথম টেস্ট না করা।
- **একাধিক store একই domain claim করতে চাইলে** — `domain` কলামে DB-level `unique()` constraint-ই এটা
  আটকাবে (দ্বিতীয় সেলার add করতে গেলে সরাসরি error), কোনো আলাদা লজিক লাগবে না।

## ৯. এই প্ল্যানে যা বাদ (deliberately, V1)

| # | কী বাদ | কেন |
|---|---|---|
| 1 | প্রতি-domain আলাদা Caddy site ব্লক (Path B) | Path A (catch-all)-এর সাথে established static-Caddy pattern বেশি মেলে; dynamic Caddy config এড়ানো হচ্ছে |
| 2 | Auto-periodic DNS re-check (cron) | V1-এ manual re-check যথেষ্ট; scope ছোট রাখা |
| 3 | একাধিক domain per store-এর UI (একসাথে অনেক domain ম্যানেজ করা) | Data model future-proof রাখা হলেও, V1 UI শুধু একটা domain ধরেই ডিজাইন হবে — পরে বাড়ানো সহজ |
| 4 | Domain transfer/ownership-dispute flow | Unique constraint-ই যথেষ্ট প্রথম দাবিদারকে protect করতে; dispute হলে admin manually resolve করবে |

এগিয়ে যাওয়ার আগে ২টা জিনিস owner-এর decision লাগবে — নিচে চ্যাটে জিজ্ঞেস করা হচ্ছে।
