# Product Variants + Product Detail Page Templates

**Status:** Built and shipped, 2026-11-09 (109/109 tests pass)

## ১. প্রসঙ্গ

আলিবাবার product page দেখিয়ে user দুটো জিনিস ধরিয়ে দিলো:
1. **Product variant সাপোর্ট নেই** — Color/Size-এর মতো option আলাদাভাবে বেছে নেওয়ার কোনো ব্যবস্থা নেই, এটা real e-commerce-এর একটা core feature, এটা না থাকাটা genuinely একটা gap (শুধু UI polish না, functional সীমাবদ্ধতা)।
2. **Product detail page-এর টেমপ্লেট** — storefront theme ও landing page-এর মতোই, product detail page-এরও একাধিক professional template থাকা উচিত, নাম বড় e-commerce সাইটের মতো (Alibaba, Daraz)।

এই দুটো একসাথে করা হচ্ছে কারণ টেমপ্লেট-গুলো variant picker UI-কেই দেখাবে — variant সিস্টেম আগে, তার উপর টেমপ্লেট।

## ২. Product Variants — ডিজাইন

- নতুন টেবিল `product_variants`: `options` (JSON, যেমন `{"Color":"Red","Size":"M"}`), `price`/`sale_price` (nullable — null মানে product-এর দাম inherit করে), `stock`, `sku`, `sort_order`।
- Seller প্রোডাক্ট এডিট পেজে **option matrix generator**: ৩টা option-slot (যেমন "Color" = "Red, Blue, Black", "Size" = "S, M, L") দিয়ে — "Generate variants" চাপলে server-side সব combination (Cartesian product, max ৬০টা cap) বানিয়ে একটা editable টেবিল দেখায় (প্রতিটা combo-র Price override/Sale price/Stock/SKU), যেটা normal "Save" দিয়েই সেভ হয়। আগে বানানো variant-এর দাম/স্টক regenerate করলেও হারায় না (options JSON মিলিয়ে match করে)।
- **Cart/Order system**: session cart-এর key এখন `"{productId}"` বা `"{productId}:{variantId}"` — `Cart`, `OrderPlacer`, `CartController` সব আপডেট হবে variant-aware হতে। Stock lock/decrement variant থাকলে variant-এর উপর হয়, না থাকলে product-এর উপর (আগের মতোই, backward compatible)।
- **Storefront**: product page-এ option group অনুযায়ী clickable chip picker (Alpine.js) — selection অনুযায়ী price/stock/SKU reactively বদলায়, সঠিক variant_id hidden field দিয়ে add-to-cart form-এ যায়। Landing page-এও (non-combo template) একই picker যুক্ত হবে।
- কোনো variant না থাকলে সবকিছু ঠিক আগের মতোই কাজ করবে (zero behavior change for existing simple products) — এটা strictly additive।

## ৩. Product Detail Page Template — ৮টা (storefront theme-এর মতো per-store setting)

নতুন `stores.product_template` কলাম (ডিফল্ট `classic`)। User "world-wide সবচেয়ে সুন্দর e-commerce" ১৩টা ব্র্যান্ডের নাম দিয়েছিল (Apple, Tesla, Nike, Gymshark, Zara, ASOS, Net-a-Porter, MUJI, Everlane, Glossier, Kylie Cosmetics, Daraz, Alibaba) — literally ১৩টা আলাদা template না বানিয়ে, একই স্টাইল-এর ব্র্যান্ডগুলো (যেমন Zara/ASOS/Net-a-Porter সবই "editorial fashion") একসাথে করে ৫টা genuinely আলাদা নতুন স্টাইল-ফ্যামিলি বানানো হলো, আগের ৩টার সাথে মিলিয়ে মোট ৮টা। নাম সরাসরি ব্র্যান্ডের বদলে বর্ণনামূলক রাখা হলো (trademark/affiliation misrepresentation এড়াতে), কিন্তু inspiration নিচে উল্লেখ করা আছে:

| Template | Inspiration | Structure |
|---|---|---|
| **Classic** | (existing, অপরিবর্তিত) | বর্তমান layout + variant picker |
| **Daraz** | daraz.com.bd | Two-column sticky buy-box, বড় discount ribbon |
| **Alibaba** | alibaba.com | বড় image + vertical thumbnail rail, tabbed content, chat-first CTA |
| **Cinematic** | Apple, Tesla | Full-bleed hero (container ভেঙে পুরো width), কেন্দ্রীভূত minimal চ্যাসি, একটাই বড় CTA |
| **Editorial** | Zara, ASOS, Net-a-Porter/MATCHES | ম্যাগাজিন-স্টাইল দুই-কলাম, serif heading, underline variant selector |
| **Bold Sport** | Nike, Gymshark | বড় uppercase টাইপ, brand-color block, bold chip-style variant |
| **Minimal Studio** | MUJI, Everlane | অতি শান্ত, কোনো badge/ribbon/motion নেই, dropdown variant selector |
| **Playful Beauty** | Glossier, Kylie Cosmetics | brand-color soft block-এ floating image, round swatch (Color option হলে আসল রং দেখায়), পুরো UI rounded-full |

সব template-ই একই server-computed variant/price ডেটা শেয়ার করে (`$variantsData`, `$optionChoices`, `$available`) — কোনো duplicate business logic নেই, শুধু markup/CSS আলাদা।

Seller-এর Appearance পেজে theme picker-এর মতোই swatch প্যাটার্ন দিয়ে বেছে নেওয়া যায়। **Plan দিয়ে gate করা আছে** — `plans.product_templates` কলাম (null = সব allowed, নির্দিষ্ট array = শুধু ওইগুলো) ঠিক `landing_templates`/`storefront_themes`-এর মতোই; Free/Subdomain plan শুধু Classic পায়, Pro সব পায়। Lock করা template seller UI-তে grey-out + "Premium" badge দেখায়, আর server-side-ও enforced। Admin `/admin/plans`-এ পুরোটা configure করতে পারে।

## ৪. যা স্কোপে নেই (deliberately)

- Variant-ভিত্তিক আলাদা ছবি (প্রতিটা color-এর জন্য আলাদা photo) — V1-এ সব variant একই product gallery শেয়ার করবে।
- Option value অনুযায়ী cross-filtering (যেমন "Red" বেছে নিলে শুধু Red-এ available size-গুলো highlight করা) — শুধু "ফাইনাল combo না থাকলে disable করা" আছে, partial-selection filtering নেই।
- Landing page-এর combo template-এ variant mixing (combo = কয়টা, variant = কোনটা — দুটো একসাথে না করে V1-এ আলাদা রাখা হচ্ছে)।

## ৫. কাজের সময় যে ২টা pre-existing bug ধরা পড়লো

- `Collection::map('trim')` — PHP-এর classic gotcha: `map()` callback-কে `($value, $key)` দুটোই পাস করে, তাই `trim($value, $key)`-এ key-টা trim()-এর "কোন character বাদ দেবে" প্যারামিটার হয়ে যায়, whitespace না! `VariantGenerator`-এ ধরা পড়লো (option values trim হচ্ছিল না), আর `LandingPageController`-এর benefits-parsing-এও একই bug ছিল (latent, কম প্রভাব) — দুটোই `fn ($v) => trim($v)` দিয়ে fix করা হলো।
- Test helper `upgradeToPro()` mid-test কল করার পর, যদি সেই User/Store object আগেই `actingAs()`/relation-access করা হয়ে থাকে, পরের `actingAs()` কল SAME stale object reuse করে — `$user->refresh()` লাগবে। `TestCase::upgradeToPro()`-এর docblock-এ এই gotcha নোট করে রাখা হলো।
