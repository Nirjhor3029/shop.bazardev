# F-commerce Single-Product Landing Pages — R&D, Plan & Implementation

**Status:** Proposal / not yet built
**Owner context:** shop.bazardev.com (multi-seller marketplace)
**Date:** 2026-11-08

## ১. প্রসঙ্গ (Why)

বাংলাদেশের অনেক F-commerce seller একটা পণ্যের জন্য একটাই page বানায় — product photo/video, details, combo/package checklist, payment info, আর সেই পেজেই একটা order form (মূলত COD, কিন্তু bKash/Nagad-ও বাড়ছে) — তারপর এই পুরো page-টাই Facebook-এ boost করে। এটা "landing page" / "one-page funnel" concept, cart-browsing-checkout এর ঝামেলা ছাড়াই সরাসরি অর্ডার নেয়।

আমাদের platform-এ এখন প্রতিটা seller-এর জন্য আছে multi-product storefront (`store.shop.bazardev.com`) যেখানে cart + checkout flow দিয়ে অর্ডার হয় — এটা browsing-এর জন্য ভালো, কিন্তু Facebook ad traffic-এর জন্য optimized না (extra click, cart step, distraction)। Seller-রা তাই বাইরে থার্ড-পার্টি landing-page tool কেনে।

**Idea:** একটা built-in "Landing Page" template system অফার করা — seller একটা পণ্য (বা combo) বেছে, একটা template চুজ করে, কয়েকটা field ভর্তি করে একটা শেয়ারযোগ্য, Facebook-boost-ready single page পাবে, যেখানে সরাসরি অর্ডার নেওয়া যাবে কোনো cart ছাড়াই।

## ২. Research findings

### ২.১ BD F-commerce landing page-এর সাধারণ গঠন

- Single scroll, mobile-first: hero (headline + photo/video) → short benefit bullets → trust/social-proof → order form — সবকিছু এক পেজে, multi-section কম কনভার্ট করে।
- Order form সবসময় ছোট রাখা হয়: **নাম, ফোন, ঠিকানা, quantity, confirm** — ৪-৫টার বেশি field থাকলেই drop-off বাড়ে, বিশেষ করে ধীরগতির মোবাইল কানেকশনে।
- BD-specific ঠিকানা কনভেনশন: Division → District → Upazila/Thana dropdown (আমাদের বর্তমান checkout-এ একটা free-text address field আছে মাত্র — এটা upgrade করার সুযোগ আছে, নিচে V2-তে)।
- ফোন নম্বর ফিল্ড প্রায়ই "Phone (bKash/Nagad)" লেবেল করা হয় — কারণ সেটাই payment contact-ও।
- COD-ই dominant; bKash/Nagad/Rocket প্রায়ই manual TrxID verification দিয়ে হয়, real-time gateway integration ছাড়া (ছোট seller-দের টুলে)।
- Countdown timer / "আজ ৫০+ বিক্রি হয়েছে" জাতীয় urgency element সাধারণ landing-page literature-এ প্রচলিত কিন্তু BD-specific verified data পাওয়া যায়নি — এটা *plausible কিন্তু unverified* ধরে নেওয়া হচ্ছে, কনফার্মড best-practice না।

### ২.২ প্রতিযোগী টুল (vendor marketing থেকে পাওয়া, independently verify করা হয়নি)

| টুল | কী করে |
|---|---|
| **Selorax** | Product description থেকে AI landing page জেনারেট করে; COD/Messenger/WhatsApp/call-center অর্ডার এক inbox-এ; bKash/Nagad/Rocket; FB Conversions API-এ "COD correction" (পরে ক্যান্সেল/রিটার্ন হলে conversion signal ঠিক করে) |
| **Dokango** | BD-native storefront builder; BD ঠিকানা ফরম্যাট; bKash/Nagad/Rocket/COD; এক-ক্লিক status; ফোন/WhatsApp অর্ডার manual entry; fraud-order ডিটেকশন; Meta Pixel; courier compare |
| **eSolver COD Checkout** | (Shopify app) custom COD popup + BD checkout + landing-page order form + FB/Google/TikTok pixel + server-side tracking |

এই সেগমেন্টের seller-রা সাধারণত দিনে ~১০–২০০ অর্ডার সামলায় — spreadsheet-এর জন্য বেশি, enterprise টুলের জন্য কম। এটাই আমাদের sweet spot।

### ২.৩ Facebook Pixel / Conversions API (CAPI)

- Standard events: `ViewContent` (ল্যান্ডিং পেজ লোড), `InitiateCheckout`/`AddToCart` (ফর্মে টাইপ শুরু), `Purchase` (অর্ডার কনফার্ম) — `Purchase`-এ `value` + `currency` অবশ্যই লাগবে, এবং ঠিক একবারই fire করতে হবে (রিফ্রেশে ডুপ্লিকেট একটা কমন bug)।
- Pixel (browser) + CAPI (server) দুটোই চালানো উচিত, একই `event_id` দিয়ে deduplicate করে — না হলে FB-তে ডবল কাউন্ট হয়।
- `fbclid` (ad click id) প্রথম পেজ-লোডেই ধরে first-party storage-এ রাখা, `_fbc` কুকি ফরম্যাটে কনভার্ট করা, এবং কোনো রিডাইরেক্টে হারিয়ে না যাওয়া — এটাই সবচেয়ে বেশি miss হওয়া জায়গা।
- iOS/ad-blocker signal loss-এর কারণে CAPI আজকাল Pixel-এর চেয়েও গুরুত্বপূর্ণ হয়ে উঠছে।

### ২.৪ Payment ও Logistics landscape

- **bKash/Nagad-এর direct merchant API** পেতে Bangladesh Bank approval লাগে — এটা per-seller marketplace মডেলে বাস্তবসম্মত না।
- **SSLCommerz** (২০১০ থেকে আছে) একটাই ইন্টিগ্রেশনে bKash + Nagad + Rocket + card + banking কভার করে, sandbox/live mode + server-callback validation সহ — এটাই বাস্তবসম্মত single aggregator path।
- Rocket-এর কোনো verified direct public API পাওয়া যায়নি — সব জায়গায় manual।
- **Courier API**: Steadfast (key+secret, ছোট seller-দের মধ্যে সবচেয়ে প্রচলিত), Pathao (sandbox + live আলাদা endpoint), RedX (token auth), Paperfly, eCourier — সবগুলোতেই create-order/track/cancel ধরনের endpoint আছে, এবং কমিউনিটি Laravel package-এও bundle করা পাওয়া যায়। অফিসিয়াল ডকুমেন্টেশন ইন্টিগ্রেশনের আগে প্রতিটা থেকে সরাসরি পড়ে নেওয়া লাগবে (ভেন্ডর পোর্টাল থেকে, সার্চে পাওয়া যায়নি)।

## ৩. Product concept

একটা নতুন entity: **Landing Page** — একটা seller-এর একটা product (বা combo-list of quantities)-এর উপর বাঁধা, store-এর সাধারণ multi-product storefront থেকে independent, নিজের slug/URL-সহ, যেটা:

1. একটা template থেকে চয়েস (visual structure fixed, content seller-এর)
2. Hero photo/video + headline + benefit bullets + trust badges + delivery/payment info
3. Combo/bundle picker (optional) — e.g. "১টা = ৫০০৳, ২টা = ৯০০৳, ৩টা = ১২০০৳ (ফ্রি ডেলিভারি)"
4. সরাসরি order form (cart ছাড়াই) — নাম, ফোন, ঠিকানা, quantity/combo, payment method
5. নিজের Facebook Pixel ID (seller নিজের ad account-এর) + CAPI wiring বিল্ট-ইন
6. পুরোটাই একটা হালকা, fast-loading, mobile-first পেজ — FB in-app browser-এর জন্য optimized

**Target user:** যে seller একটা hero/promo পণ্য নিয়ে Facebook-এ টাকা খরচ করে ad চালাতে চায়, এবং চায় click করে সরাসরি order form-এ, কোনো distraction ছাড়া।

**আমরা যা করি না:** আমরা ad বানাই/রান করি না — সেটা seller নিজের FB ad account থেকেই করবে। আমরা শুধু ad-ready page + conversion tracking সাপ্লাই করি।

## ৪. Template catalog (V1: ৩টা)

| টেমপ্লেট | কাদের জন্য | বিশেষত্ব |
|---|---|---|
| **Classic COD Funnel** | বেশিরভাগ single-product seller | hero image, bullet benefits, trust badges, simple order form — default/safe choice |
| **Combo/Bundle Offer** | যারা "২টা নিলে ছাড়" টাইপ অফার দেয় | combo picker প্রথম fold-এ prominent, price per-combo দেখায় |
| **Video-first** | যাদের ডেমো/unboxing ভিডিও আছে | hero-এ ভিডিও autoplay (muted) বা click-to-play, নিচে বাকি সব এক |

প্রতিটা টেমপ্লেট structurally আলাদা (storefront থিমের মতো শুধু রং-radius বদল না) — এটা আগের storefront theme elevation কাজের principle-এর সাথেই সামঞ্জস্যপূর্ণ ([[design-elevation]] যদি future-এ এই MD-কে reference করে কাজ করা হয়)।

## ৫. Data model plan

নতুন migration-গুলো (কোনো existing টেবিল ভাঙবে না):

```php
// landing_pages
$table->id();
$table->foreignId('store_id')->constrained()->cascadeOnDelete();
$table->foreignId('product_id')->constrained()->cascadeOnDelete();
$table->string('slug', 60); // unique per store — shop.bazardev.com/.../lp/{slug}
$table->string('template', 20)->default('classic'); // classic | combo | video
$table->string('headline', 150)->nullable();
$table->text('subheadline')->nullable();
$table->string('video_url')->nullable();
$table->json('benefits')->nullable();     // ["ফ্রি ডেলিভারি", "৭ দিন রিটার্ন", ...]
$table->json('combos')->nullable();       // [{qty:1, price:500}, {qty:2, price:900, label:"জনপ্রিয়"}]
$table->string('pixel_id', 40)->nullable();
$table->boolean('is_published')->default(false);
$table->timestamps();
$table->unique(['store_id', 'slug']);

// orders টেবিলে যুক্ত হবে (tracking-এর জন্য)
$table->string('source', 20)->default('storefront'); // storefront | landing
$table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
$table->string('fbclid', 100)->nullable();
$table->string('utm_source', 60)->nullable();
```

Combo-কে আলাদা টেবিল না করে `landing_pages.combos` JSON রাখা হচ্ছে — কারণ combo শুধু landing page context-এই মিনিংফুল (সাধারণ storefront গ্রিডে "২টা নিলে ছাড়" দেখানোর দরকার নেই এখন), আর existing `Store::look()` JSON-settings প্যাটার্নের সাথেও মেলে।

## ৬. Technical architecture

**Route:** `{store}.shop.bazardev.com/lp/{slug}` — storefront-এর বর্তমান route group-এর ভেতরেই (same `store.host` middleware), কিন্তু আলাদা controller ও layout (storefront layout পুরোটা reuse না, হালকা standalone blade layout — cart/header নেভিগেশন লাগবে না, যাতে page আরও fast লোড হয়)।

```php
Route::get('/lp/{slug}', [LandingPageController::class, 'show']);
Route::post('/lp/{slug}/order', [LandingPageController::class, 'order'])->middleware('throttle:10,1');
```

**Order flow:** বর্তমান `OrderPlacer::place()` সেশন-`Cart` নির্ভর। Landing page-এ cart লাগবে না — তাই `OrderPlacer`-এ একটা নতুন মেথড `placeSingle(Store $store, Product $product, int $qty, array $customer, ?int $comboIndex)` যুক্ত হবে, যেটা একই validation/stock-lock/transaction লজিক reuse করবে কিন্তু raw cart array না বানিয়ে সরাসরি একটা item নিয়ে কাজ করবে। (সেশন cart touch করবে না, তাই normal browsing cart-এর সাথে কনফ্লিক্ট হবে না।)

**fbclid capture:** landing page-এর `show()`-এ query string-এ `fbclid` থাকলে order-এ পাস করার জন্য hidden form field-এ বসিয়ে রাখা (session-ও রাখা যায়, কিন্তু hidden field সবচেয়ে simple — ad-এর single page visit-এই বেশিরভাগ conversion হয়)।

**Pixel + CAPI:**
- পেজে ক্লায়েন্ট-সাইড Meta Pixel snippet (landing page-এর `pixel_id` দিয়ে) — `PageView` + `ViewContent` অটো-ফায়ার।
- অর্ডার কনফার্ম হলে: client-side `Purchase` event (order confirmation পেজে) + server-side CAPI call (queued job, `OrderPlaced` event listener) — দুটোতেই `event_id = order.token` ব্যবহার করে dedupe।
- CAPI access token সেলার নিজের Meta Business Manager থেকে জেনারেট করে appearance/landing-settings-এ পেস্ট করবে (platform নিজে FB app বানাবে না — scope ছোট রাখা)।

## ৭. Seller-facing builder UI

নতুন seller section: **"Landing Pages"**
- List view: আগের landing page-গুলো, প্রতিটার preview link + order count
- Create/edit form: Product বাছাই → Template বাছাই (visual swatch, appearance-editor-এ যেমন থিম swatch করা হয়েছে সেই প্যাটার্ন) → headline/subheadline/video URL/benefits/combo list → Pixel ID (optional) → Publish toggle
- Publish না করা পর্যন্ত পেজ live না (draft অবস্থা safe রাখার জন্য)

## ৮. Payment ও Logistics — ধাপে ধাপে (risk বাড়ানো ছাড়া)

| ধাপ | কী | কেন এই অর্ডারে |
|---|---|---|
| **Phase 1 (MVP)** | COD + bKash/Nagad/Rocket manual TrxID (বর্তমান checkout-এ যেটা আছে, ঠিক সেটাই reuse) | Zero নতুন payment integration risk, landing page feature দ্রুত ship করা যায় |
| **Phase 2** | SSLCommerz aggregator (real-time bKash/Nagad/Rocket/card) | একটাই ইন্টিগ্রেশনে সব gateway কভার হয়; direct bKash/Nagad merchant API-এর Bangladesh Bank approval ঝামেলা এড়ানো যায় |
| **Phase 3** | Courier API — Steadfast প্রথমে (সবচেয়ে popular ছোট seller-দের মধ্যে), পরে Pathao/RedX | Seller order panel থেকেই এক-ক্লিকে shipment বানানো + tracking link — কিন্তু প্রতিটা vendor portal থেকে actual API doc পড়ে implement করা লাগবে (সার্চে অফিসিয়াল ডক পাওয়া যায়নি) |

## ৯. Roadmap

1. ✅ **M1 — ভিত্তি (done, 2026-11-08):** migrations + `LandingPageController` + "Classic" টেমপ্লেট + direct order flow (cart ছাড়া, `OrderPlacer::placeSingle()`) + seller builder (basic form) + cross-store ownership protection
2. ✅ **M2 — Combo + টেমপ্লেট ভ্যারাইটি (done, 2026-11-08):** combo/bundle picker (server-stored price, client কখনো price পাঠাতে পারে না) + Combo ও Video-first (YouTube embed / direct video URL, image fallback) টেমপ্লেট + seller form-এ template picker
3. ✅ **M3 — FB tracking (done, 2026-11-08):** client Meta Pixel (PageView + ViewContent) + server-side Conversions API Purchase event (queued job, SHA-256 hashed phone/email, `fbc`/`fbp` matching params) + fbclid capture into a hidden field, deduped against the client Pixel via a shared `event_id` (the order token) + per-landing-page Pixel ID/CAPI token (token encrypted at rest, masked in the edit form)
4. **M4 — SSLCommerz:** real-time gateway payment
5. **M5 — Courier API:** Steadfast integration first

**Operational note for M3:** the CAPI job is queued (`App\Jobs\SendMetaPurchaseEvent`); production uses the `database` queue driver, so a queue worker (`php artisan queue:work`) must actually be running for these events to send - otherwise they sit in the `jobs` table unsent.

প্রতি ধাপ independently ship করা যাবে — M1 শেষ হলেই seller-রা landing page ব্যবহার শুরু করতে পারবে, বাকিগুলো পরে যুক্ত হবে feature flag ছাড়াই (নতুন column/nullable field, কোনো breaking change নেই)।

## ১০. Risk ও Open questions

- **bKash/Nagad direct API কখনো আমাদের নিজেদের নামে হবে না** — marketplace মডেলে per-seller merchant onboarding বাস্তবসম্মত না। SSLCommerz-ই বাস্তব পথ, এটা user-কে স্পষ্টভাবে জানিয়ে রাখা দরকার (expectation set করা)।
- **URL structure decision লাগবে:** `store.shop.bazardev.com/lp/{slug}` (brand-consistent, বর্তমান SEO/subdomain প্যাটার্নের সাথে মেলে) বনাম একটা shorter root-level alias (e.g. `shop.bazardev.com/go/{code}`) বিজ্ঞাপনের লিংক copy-paste-এর জন্য সহজ। সুপারিশ: subdomain-ই রাখা, কিন্তু চাইলে একটা optional short-link redirect যুক্ত করা যায় পরে।
- **Countdown/urgency element** — BD-specific প্রমাণিত best-practice না, তাই V1-এ স্কিপ করে রাখাই নিরাপদ (over-promise না করা)।
- **Facebook ad চালানো সেলারের দায়িত্ব** — platform ad spend/campaign manage করবে না, শুধু conversion-ready page + tracking সাপ্লাই করবে। এটা scope-এ স্পষ্ট থাকা দরকার যাতে পরে confusion না হয়।
- **Courier API-এর জন্য official docs এখনো পড়া হয়নি** — Phase 3 শুরুর আগে Steadfast/Pathao/RedX-এর actual developer portal থেকে ডকুমেন্টেশন verify করা দরকার (এই রিসার্চে vendor portal URL-এর আন্দাজ পাওয়া গেছে, কিন্তু official spec না)।

---
*এই ডকুমেন্ট একটা plan/proposal — কোনো কোড এখনো লেখা হয়নি। পরবর্তী কথোপকথনে M1 থেকে implementation শুরু করা যায়।*
