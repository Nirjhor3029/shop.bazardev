# Revenue Model — shop.bazardev.com

**Status:** Proposal / not yet built
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

## ৩. প্রস্তাবিত Tier structure (বাস্তব existing feature-এর উপর বেস করে)

| | **Free** | **Pro** (সুপারিশ: ৳৫৯৯–৯৯৯/মাস) | **Business** (future, ৳১৯৯৯+/মাস) |
|---|---|---|---|
| Store + subdomain | ✅ | ✅ | ✅ |
| Products | ২০-৩০ পর্যন্ত | Unlimited | Unlimited |
| Storefront theme | Minimal, Boutique (২টা) | সব ৫টা (Bold/Vibrant/Mono-সহ) | সব + custom CSS (future) |
| Landing page | ১টা, Classic টেমপ্লেট only | Unlimited, সব টেমপ্লেট (Combo/Video) | Unlimited |
| Payment | COD + manual bKash/Nagad/Rocket | + SSLCommerz real-time gateway | + (future) multi-gateway |
| Facebook tracking | — | Pixel + CAPI | Pixel + CAPI + (future) TikTok/Google |
| "Made with Bazardev Shop" footer | থাকবে (ফ্রি মার্কেটিং, বিনামূল্যে ad) | বাদ দেওয়া যাবে | বাদ |
| Staff/team login | ১ জন (owner) | ১ জন | একাধিক (future) |
| Support | community/FAQ | priority | dedicated |

**Free tier ইচ্ছাকৃতভাবে generous রাখা** — acquisition-focused, আর "Made with Bazardev Shop" ব্র্যান্ডিং (যা already আছে কোডে) বিনামূল্যে marketing করে দেয় platform-এর জন্য।

## ৪. Add-on (tier-independent, usage-based) revenue

- **Marketplace homepage boost**: `shop.bazardev.com`-এর (MarketController-এর) হোমপেজে "Featured seller" স্লট বিক্রি — একটা নতুন paid placement, কোনো existing feature touch করে না
- **Extra landing page** (Free tier-এর ১টার কোটা পার হলে, এক-কালীন বা মাসিক ছোট ফি)
- (ভবিষ্যতে, M5 courier API বানানো হলে) courier auto-booking একটা Pro/Business-exclusive feature হতে পারে

## ৫. প্রয়োজনীয় টেকনিক্যাল ভিত্তি (এখনো কিছুই নেই)

1. **`stores` টেবিলে `plan` + `plan_expires_at` কলাম** (migration) — ডিফল্ট সবাই `'free'`
2. **Feature-gate check** প্রতিটা জায়গায় যেখানে paid feature ব্যবহার হয়:
   - `Seller\LandingPageController::store()` — Free plan-এ ১টার বেশি ল্যান্ডিং পেজ হলে block + upgrade prompt
   - `Seller\AppearanceController` — Free plan-এ bold/vibrant/mono থিম সিলেক্ট করলে block
   - `Seller\SettingsController` — Free plan-এ SSLCommerz enable করতে না দেওয়া
   - landing page-এ pixel_id/capi_token সেভ করার অনুমতি Free-তে না দেওয়া
3. **Billing flow**: platform নিজের SSLCommerz merchant account (seller-দের থেকে আলাদা) দিয়ে seller-কে subscription চার্জ করবে। BD-তে true auto-recurring billing সাধারণত সহজলভ্য না (SSLCommerz-এর recurring/subscription ফিচার আছে কিনা — **unverified, implement করার আগে যাচাই করতে হবে**, এই সেশনের courier/payment research-এর মতোই সতর্কতা) — তাই বাস্তবসম্মত শুরু: মাসিক "renew now" manual flow (SaaS টুল যেমন সাধারণত BD-তে করে), auto-recurring পরে upgrade করা যায়।
4. **Trial period**: নতুন signup-এ ১৪ দিন Pro ফ্রি ট্রায়াল (conversion বাড়ানোর জন্য প্রমাণিত প্যাটার্ন), তারপর Free-এ নেমে আসা বা upgrade করা
5. **Admin panel**: `AdminController`-এ seller-এর plan দেখা/manually extend/comp করার অপশন (support/goodwill-এর জন্য জরুরি)

## ৬. Rollout ধাপে ধাপে

1. **Phase 1 — ভিত্তি**: `plan` কলাম + সব ফিচার-গেট চেক বানানো, সবাইকে `'free'` ডিফল্ট (রেভিনিউ এখনো শুরু হয় না, কিন্তু infrastructure রেডি হয়)
2. **Phase 2 — Billing**: Seller-facing "Upgrade" পেজ + platform-এর নিজের SSLCommerz দিয়ে subscription checkout + admin-এ manual renew/extend
3. **Phase 3 — Enforcement**: Free plan-এ থাকা existing seller-দের grandfather করে একটা গ্রেস পিরিয়ড দেওয়া (যেমন ৩০ দিন নোটিস), তারপর gate চালু
4. **Phase 4 — Add-on**: Marketplace boost placement + extra-landing-page ফি

## ৭. Risk ও Open question

- **Existing seller-দের সাথে expectation**: platform এখনো production-এ যায়নি বলে এখনই monetization চালু করা তুলনামূলক কম রিস্কি — কিন্তু যদি কোনো seller আগে থেকেই ব্যবহার করে থাকে, grandfathering/নোটিস পিরিয়ড জরুরি, নাহলে বিশ্বাস ভাঙবে
- **SSLCommerz recurring billing সাপোর্ট করে কিনা** — verify করা হয়নি, Phase 2 শুরুর আগে official docs থেকে চেক করা লাগবে
- **"Free tier কতটা generous রাখা উচিত"** — এটা একটা ব্যবসায়িক সিদ্ধান্ত (conversion vs acquisition trade-off), ফাইনাল সংখ্যা (৳ এবং quota) user/business owner-কে ঠিক করতে হবে — উপরের সংখ্যাগুলো শুরু করার জন্য একটা reasonable sketch, final নয়

---
*এই ডকুমেন্ট একটা plan/proposal — কোনো billing/plan কোড এখনো লেখা হয়নি।*
