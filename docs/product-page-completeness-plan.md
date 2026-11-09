# Product Detail Page — Completeness Plan

**Status:** Phase 1 built and shipped, 2026-11-08 (116/116 tests pass). Phase 2 (reviews/Q&A/bundles) still not started.

## ১. প্রসঙ্গ

৮টা product-detail template + variant system শিপ করার পর user জিজ্ঞেস করলো: "Normal product details এ যা যা থাকে সব add করছো না? Review করো।" Review করে যা পাওয়া গেলো তার ভিত্তিতে এই plan।

## ২. এখন যা আছে (৮টা template + shared data layer জুড়ে)

Image gallery, title, price+discount%, stock status, variant picker, qty selector, add-to-cart, WhatsApp order/ask, delivery charge, payment method chips, description, share buttons, related products, breadcrumb, SEO (OG+JSON-LD), sticky mobile bar।

## ৩. Gap-গুলো — effort অনুযায়ী ২টা ধাপে ভাগ করা হলো

### Phase 1 — এই ব্যাচে build হবে (কম effort, বেশি value, কোনো নতুন major subsystem লাগবে না)

| # | Feature | Design |
|---|---|---|
| 1 | **Specifications table** | `products.specs` নতুন JSON column (nullable), `[{key, value}]` array — ঠিক landing page-এর "benefits" repeater-এর মতো UI দিয়ে seller product-edit পেজে key/value row add/remove করতে পারবে। Product page-এ description-এর নিচে একটা clean 2-column table হিসেবে দেখাবে (সব ৮ template-এ shared partial `storefront.product-templates._specs` include করে, যাতে markup duplicate না হয়)। |
| 2 | **SKU দেখানো** | Variant-এর SKU আগে থেকেই ডাটাবেজে আছে, storefront-এ কোথাও দেখানো হয় না। Selected variant-এর SKU (বা কোনো variant না থাকলে কিছুই না — product-এর নিজের SKU নেই) qty selector-এর পাশে ছোট `text-gray-400` টেক্সট হিসেবে দেখাবে, Alpine-এর existing `variant` getter থেকেই আসবে, নতুন কোনো business logic লাগবে না। |
| 3 | **Delivery time estimate** | `stores.delivery_estimate` নতুন nullable string column (যেমন "২-৪ দিনের মধ্যে ডেলিভারি"), seller Settings পেজে delivery charge-এর পাশেই একটা optional text field। থাকলে delivery charge line-এর সাথেই দেখাবে, না থাকলে কিছু বদলাবে না (existing store-গুলো জন্য zero behavior change)। |
| 4 | **Size guide** | `products.size_guide` নতুন nullable text column (seller free-text, যেমন একটা size chart বা fit note লিখে দিতে পারবে)। যদি product-এর option-এ "Size" থাকে এবং এই ফিল্ড পূরণ করা থাকে, variant picker-এর পাশে "Size guide" লিংক দেখাবে যা Alpine modal/dialog-এ টেক্সট খুলবে। না থাকলে লিংকই দেখাবে না। |
| 5 | **Trust badge polish** | এখন payment method chip-গুলো ছোট inline row হিসেবে আছে প্রতিটা template-এ (এটা আসলে কম gap, আগে যা ভেবেছিলাম তার চেয়ে কম সমস্যা)। শুধু polish: `sf-trust-chip` style আরেকটু prominent করা + delivery charge/estimate-এর সাথে একই card-এ গুছিয়ে রাখা, যাতে কেনার decision-এর মুহূর্তে trust signal একসাথে দেখা যায়। নতুন column/logic লাগবে না, শুধু existing block-এর layout tweak। |
| 6 | **Image zoom/lightbox** | ছবিতে ক্লিক করলে full-screen zoom view (Alpine-এ `x-data="{ zoom: false }"` + simple overlay, pinch/scroll zoom না — শুধু বড় করে দেখানো)। ৮টা template-এ gallery markup প্রায় একই প্যাটার্নের, তাই একটা ছোট shared Alpine snippet কপি-এডাপ্ট করে সবগুলোতে বসানো যাবে। |
| 7 | **Recently viewed products** | Backend ছাড়াই — client-side `localStorage`-এ last ~8টা product (id+url+name+image+price) রাখা, product page লোড হলে push করা, এবং সেই পেজেই (বা homepage-এ) "Recently viewed" strip হিসেবে দেখানো যদি কিছু থাকে। কোনো নতুন টেবিল/migration লাগবে না, pure frontend। |
| 8 | **Wishlist (session-based, V1)** | Customer login নেই (WhatsApp-centric model), তাই full backend wishlist overkill। V1: `localStorage`-এ product id-এর list রাখা, heart icon toggle, "My wishlist" পেজ client-side render করে (id দিয়ে ইতিমধ্যে load হওয়া product card data ব্যবহার করে, বাছোট public API endpoint দিয়ে id-array পাঠিয়ে card render করা)। Device-specific, cross-device sync নেই — এটা পরে account system আসলে upgrade করা যাবে। |

### Phase 2 — আলাদা বড় feature, এই batch-এ স্কোপে নেই (পরে আলাদা plan+approval লাগবে)

| # | Feature | কেন আলাদা |
|---|---|---|
| 9 | **Reviews & Ratings** | সবচেয়ে বড় real gap, কিন্তু নতুন `product_reviews` টেবিল, moderation flow (seller/admin approve/reject), average rating caching, fake-review abuse prevention (ideally order-linked "verified purchase" গেট) লাগবে — এটা একটা নিজের মতো সম্পূর্ণ feature, এই batch-এ ঢোকালে স্কোপ অনেক বড় হয়ে যাবে। |
| 10 | **Q&A section** | Reviews-এর মতোই নতুন subsystem + moderation লাগবে, নিচের priority-তে। |
| 11 | **Frequently bought together / curated bundles** | Combo pricing আগে থেকেই landing page-এ আছে (অন্য context-এ), কিন্তু product page-এ "curated cross-sell" আলাদা admin UI লাগবে এটা ঠিক করার জন্য কোন product-গুলো bundle হবে — পরের ধাপে। |

## ৪. Phase 1 বাস্তবায়নের ক্রম (dependency অনুযায়ী)

1. Migration: `products.specs` (JSON, nullable), `products.size_guide` (text, nullable), `stores.delivery_estimate` (string, nullable)
2. Seller `ProductController` — specs repeater validation/sync (benefits-parsing bug আগে fix করা হয়েছিল, specs-এও সেই `fn ($v) => trim($v)` প্যাটার্ন ব্যবহার হবে)
3. Seller product form — specs repeater UI + size guide textarea
4. Seller Settings/Appearance — delivery_estimate field
5. Shared partial `storefront.product-templates._specs` — সব ৮ template-এ include
6. SKU display + size-guide-link + zoom overlay — ৮ template-এ ছোট markup addition (Alpine getter/data প্রায় একই বলে duplicate effort কম)
7. Recently-viewed + wishlist — client-side JS (storefront layout-এ shared script), heart icon ৮ template-এ, "My wishlist" নতুন ছোট route+view
8. Tests: specs/size_guide validation, template rendering loop (existing `test_each_product_template_renders_with_and_without_variants` pattern এ নতুন field যুক্ত হবে), delivery_estimate display
9. Pint + full suite + docs update + commit

## ৫. Build করার সময় একটা ছোট scope সিদ্ধান্ত

Wishlist heart icon সব জায়গায় product card-এ (home, related, recently-viewed, wishlist page) আছে, কিন্তু product detail page-এর মূল product-এর জন্য আলাদা heart button যুক্ত করা হয়নি (৮টা template-এ আরও একটা edit করা লাগতো, আর card-গুলো দিয়েই wishlist reachable - related products সবসময় product page-এর নিচেই থাকে)। চাইলে পরে যুক্ত করা যাবে।

## ৬. স্কোপে নেই (deliberately, এই plan-এও)

- Variant-ভিত্তিক আলাদা ছবি (আগের plan-এও ছিল না, এখনো না)
- Fabricated urgency/social-proof ("X জন দেখছে") — সবসময়ই বাদ
- Multi-currency / bulk-MOQ pricing tier — B2C context-এ কম relevant
