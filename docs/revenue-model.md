# Revenue Model — shop.bazardev.com

**Status:** Phase 1 (plan system + feature gating) built and shipped, 2026-11-08. Phase 2 (self-serve billing) not started.
**Date:** 2026-11-08

## ১. প্রসঙ্গ

এখন পর্যন্ত platform-এ seller-দের জন্য সবকিছু ফ্রি: store creation, ৫টা storefront theme, unlimited product, landing page (৩ টেমপ্লেট, combo/video), Meta Pixel+CAPI, SSLCommerz integration — সব। কোনো `plan`/`billing` system নেই (কোডবেসে চেক করে কনফার্ম করা হলো — `Store` model-এ কোনো plan field নেই)। Revenue আনতে হলে প্রথমে একটা monetization layer বানাতে হবে, যেটা এখনো নেই।

## ২. একটা জরুরি architectural সীমাবদ্ধতা — কেন pure commission কাজ করবে না

Marketplace-এর মতো মনে হলেও, **payment flow platform-এর হাত দিয়ে যায় না**:
- COD: buyer সরাসরি seller-কে ক্যাশ দেয়
- bKash/Nagad/Rocket (manual): buyer সরাসরি seller-এর personal/merchant নম্বরে সেন্ড মানি করে
- SSLCommerz: **seller নিজের** Store ID/Password ব্যবহার করে, platform কখনো টাকা হাতে ধরে না (এটা ইচ্ছাকৃত ডিজাইন ডিসিশন ছিল, যাতে platform payment-intermediary/money-handling liability না নেয়)

এর মানে **প্রতি-অর্ডার % commission কাটা প্রযুক্তিগতভাবে সহজ না** — platform-এর কাছে actual payment confirm হওয়ার কোনো reliable সিগন্যাল নেই (COD/manual-এর ক্ষেত্রে), আর SSLCommerz-এর ক্ষেত্রেও টাকা সরাসরি seller-এর account-এ যায়, platform wallet-এ আসে না। এটা করতে হলে platform-কেই payment intermediary বনে যেতে হবে — বড় regulatory/liability shift, যা আমরা আগেই এড়িয়ে গেছি।

**সিদ্ধান্ত: SaaS subscription + usage add-on-কে primary revenue lever ধরা হচ্ছে**, commission-ভিত্তিক মডেল না। এখানে seller platform-কে সাবস্ক্রিপশন ফি দেয় (এটা normal SaaS billing, platform নিজেই merchant — এটা উপরের সীমাবদ্ধতার সাথে কনফ্লিক্ট করে না, কারণ এখানে "buyer" হচ্ছে seller নিজে)।

## ৩. Tier structure — এখন বাস্তবে যা implement হয়েছে

Tier কোনো hardcoded enum না — একটা `plans` টেবিল, super admin থেকে fully editable (`/admin/plans`): price, product limit, landing-page limit, কোন কোন landing টেমপ্লেট allowed, কোন কোন storefront থিম allowed, subdomain/SSLCommerz/Pixel-tracking/branding-removal on-off — সব admin UI থেকে change করা যায়, কোনো নতুন কোড deploy ছাড়াই। নিচের টেবিল হলো যে ৩টা plan দিয়ে seed করা হয়েছে (শুরুর পয়েন্ট, admin যখন চায় বদলাতে পারে):

| | **Free** (default) | **Subdomain** (৳১০/মাস) | **Pro** (৳৫৯৯/মাস) |
|---|---|---|---|
| Store URL | `shop.bazardev.com/{slug}` (path) | `{slug}.shop.bazardev.com` | `{slug}.shop.bazardev.com` |
| Products | ২০ পর্যন্ত | ২০ পর্যন্ত | Unlimited |
| Storefront theme | Minimal, Boutique | Minimal, Boutique | সব ৫টা |
| Landing page | ১টা, Classic only | ১টা, Classic only | Unlimited, সব টেমপ্লেট |
| SSLCommerz / Pixel+CAPI | — | — | ✅ |
| "Made with Bazardev Shop" footer | থাকবে | থাকবে | বাদ |

**মূল পরিবর্তন (idea থেকে implementation-এ):** branded subdomain নিজেই এখন একটা বিক্রয়যোগ্য আইটেম — normal/ফ্রি সাইন-আপ পায় `shop.bazardev.com/{slug}` path URL, সাবডোমেইন চাইলে ছোট আলাদা ফি (Subdomain plan)। "Made with Bazardev Shop" ফুটার শুধু যারা এখনো upgrade করেনি তাদের পেজে থাকে — এটা ফ্রি marketing platform-এর জন্য।

**Custom domain** — Settings পেজে একটা disabled placeholder field আছে ("coming soon"), কিন্তু কোনো backend কাজ এখনো হয়নি (ইচ্ছাকৃতভাবে, এটা একটা আলাদা future ফিচার)।

## ৪. Add-on (tier-independent, usage-based) revenue

- **Marketplace homepage boost**: `shop.bazardev.com`-এর (MarketController-এর) হোমপেজে "Featured seller" স্লট বিক্রি — একটা নতুন paid placement, কোনো existing feature touch করে না
- **Extra landing page** (Free tier-এর ১টার কোটা পার হলে, এক-কালীন বা মাসিক ছোট ফি)
- (ভবিষ্যতে, M5 courier API বানানো হলে) courier auto-booking একটা Pro/Business-exclusive feature হতে পারে

## ৫. টেকনিক্যাল implementation (built, 2026-11-08)

1. ✅ **`plans` টেবিল** (`App\Models\Plan`) — name, slug, price, max_products, max_landing_pages, landing_templates (nullable json, null = সব), storefront_themes (nullable json, null = সব), allow_subdomain, allow_sslcommerz, allow_pixel_tracking, remove_branding, is_default, is_active, sort_order। `stores.plan_id` + `stores.plan_expires_at` (null = কখনো expire হয় না)। `Store::activePlan()` — plan না থাকলে বা expire হয়ে গেলে Free-তে fallback করে (ভাঙে না)।
2. ✅ **Super admin plan CRUD** (`/admin/plans`) — পুরোপুরি admin-configured, কোনো hardcoded tier নেই। `/admin`-এ প্রতিটা store-এর পাশে একটা dropdown দিয়ে plan সরাসরি change করা যায় (manual assignment — যেমন admin সরাসরি bKash-এ পেমেন্ট পেয়ে upgrade করে দিতে পারে)।
3. ✅ **Feature-gate enforcement**:
   - `Seller\ProductController::store()` — product limit পার হলে block
   - `Seller\LandingPageController` — landing-page limit + template allowlist + pixel/CAPI allowlist, create ও edit দুটোতেই
   - `Seller\AppearanceController::update()` — theme allowlist
   - `Seller\SettingsController::update()` — SSLCommerz enable শুধু allowed plan-এ
   - `storefront/layout.blade.php` — "Made with Bazardev Shop" ফুটার `remove_branding` অনুযায়ী
   - Seller UI-তেও visual lock (grey-out + "Premium" ব্যাজ) আছে, শুধু সার্ভার-সাইড reject না
4. ✅ **URL routing split**: `Store::url()` প্ল্যান অনুযায়ী path (`shop.bazardev.com/{slug}`) বা subdomain (`{slug}.shop.bazardev.com`) রিটার্ন করে। দুটো route group-ই (domain + path) একই controller code শেয়ার করে — duplicate লজিক নেই। Subdomain নিজে থেকে কাজ করে না যদি plan allow না করে (404)। Path URL সবসময় কাজ করে, plan যেটাই হোক।
5. ⛔ **Billing flow (Phase 2, not built)**: Seller-facing self-serve "Upgrade & pay" পেজ এখনো নেই। Platform-এর নিজের SSLCommerz merchant account (seller-দের থেকে আলাদা) দিয়ে subscription charge করার ফিচার implement করা হয়নি — এর জন্য (ক) platform-এর নিজের SSLCommerz sandbox/live account লাগবে, (খ) recurring billing support আছে কিনা verify করতে হবে (unverified)। এখন admin manually plan assign করে।
6. ⛔ **Trial period**: implement করা হয়নি — নতুন signup সরাসরি Free-তে যায়।

## ৬. Rollout status

1. ✅ **Phase 1 — ভিত্তি (done, 2026-11-08)**: plan system + সব ফিচার-গেট + URL split + admin manual assignment — উপরে বিস্তারিত।
2. ⛔ **Phase 2 — Self-serve billing**: এখনো শুরু হয়নি। লাগবে: platform-এর নিজের SSLCommerz merchant account, seller-facing upgrade/checkout UI, recurring billing verify।
3. **Phase 3 — Enforcement grace period**: প্রজেক্ট এখনো production-এ যায়নি, তাই existing user নিয়ে grandfathering-এর দরকার এখনো পড়েনি — production-এ যাওয়ার আগে revisit করা লাগবে যদি তখন কোনো real seller আগে থেকেই থাকে।
4. **Phase 4 — Add-on** (marketplace boost placement, extra-landing-page ফি): এখনো করা হয়নি।

## ৭. Risk ও Open question

- **Existing seller-দের সাথে expectation**: platform এখনো production-এ যায়নি বলে এখনই monetization চালু করা তুলনামূলক কম রিস্কি — কিন্তু যদি কোনো seller আগে থেকেই ব্যবহার করে থাকে, grandfathering/নোটিস পিরিয়ড জরুরি, নাহলে বিশ্বাস ভাঙবে
- **SSLCommerz recurring billing সাপোর্ট করে কিনা** — verify করা হয়নি, Phase 2 শুরুর আগে official docs থেকে চেক করা লাগবে
- **"Free tier কতটা generous রাখা উচিত"** — এটা একটা ব্যবসায়িক সিদ্ধান্ত (conversion vs acquisition trade-off), ফাইনাল সংখ্যা (৳ এবং quota) user/business owner-কে ঠিক করতে হবে — উপরের সংখ্যাগুলো শুরু করার জন্য একটা reasonable sketch, final নয়

---
*Phase 1 (plan system, feature gates, URL split, admin manual plan assignment) build হয়ে গেছে ও push করা হয়েছে, 99/99 test pass। Phase 2 (self-serve seller billing) এখনো শুরু হয়নি।*
