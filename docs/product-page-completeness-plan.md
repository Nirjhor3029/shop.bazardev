# Product Detail Page — Completeness Plan

**Status:** Everything in this plan is now shipped (2026-11-08 → 2026-11-09), including both Phase 2 items and the follow-up batch: variant images, main-page wishlist, Reviews & Ratings, Q&A, and frequently-bought-together bundles. 134/134 tests pass.

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

## ৫. Follow-up ব্যাচ (2026-11-09) — variant images + main-page wishlist

- `product_variants.image_id` (nullable FK → `product_images`) — seller variant row-এ একটা `<select>` দিয়ে কোন uploaded image সেই combo (সাধারণত Color)-এর জন্য দেখাবে বেছে দিতে পারে। স্টোরফ্রন্টে variant select করলে gallery-র উপর সেই image overlay হয়ে বসে (৮ template-এই, zoom/wishlist overlay-এর মতোই sibling + absolute-position প্যাটার্নে, কোনো carousel/scroll logic touch না করে)। Cross-product image_id সার্ভার-সাইডে ignore হয় (`$product->images()->pluck('id')` দিয়ে validate)।
- মূল product detail page-এর primary product-এও এখন wishlist heart button আছে (card-গুলোর মতোই `.sf-wish-btn` reuse করে, existing delegated click listener আর localStorage sync দিয়েই কাজ করে, নতুন JS লাগেনি)।

## ৬. Reviews & Ratings (2026-11-09) — শিপড

Customer login নেই, তাই "verified purchase" গেট করা হয়েছে অর্ডারের secret token দিয়ে, নতুন কোনো auth system ছাড়াই:

- `product_reviews` টেবিল (store_id, product_id, order_id, customer_name snapshot, rating 1-5, comment, status pending/approved/rejected), `(order_id, product_id)` unique — এক অর্ডারে এক product-এর জন্য একটাই রিভিউ।
- Submission: অর্ডার `delivered` হওয়ার পর, customer নিজের order page (`/order/{token}`)-এই প্রতিটা item-এর নিচে rating+comment দিয়ে submit করতে পারে — কোনো আলাদা লগইন বা নতুন লিংক লাগে না। Route `product_id`-টা ওই order-এর item-এ আছে কিনা check করে (403 না থাকলে), আর duplicate submit হলে DB-level unique constraint + race-safe catch দিয়ে handle হয়।
- Moderation: Seller dashboard-এ নতুন "Reviews" ট্যাব (pending count badge সহ, ঠিক Orders-এর "new" badge-এর মতো) — approve/reject করলে `Product::recomputeRating()` কল হয়ে `rating_avg`/`reviews_count` (cached columns) আপডেট হয়, storefront-এ প্রতি page-load-এ aggregate query লাগে না।
- Storefront: approved review থাকলেই rating summary + latest ১০টা review `_reviews` partial-এ (৮ template-এই `_specs`-এর ঠিক পরে)।
- Rate-limited (`throttle:10,1`) review-submission route, cross-store/cross-product leak ঠেকাতে multi-layer check (route-model-binding scope + explicit item-membership check)।

## ৭. Q&A section (2026-11-09) — শিপড

Reviews-এর মতো order-token গেট লাগেনি — কারণ Q&A-র পুরো উদ্দেশ্যই কেনার আগে প্রশ্ন করতে পারা, তাই product page-এই সরাসরি "Ask a question" ফর্ম (নাম + প্রশ্ন, কোনো login/order লাগে না, `throttle:5,1`)।

- `product_questions` টেবিল (store_id, product_id, customer_name, question, answer nullable, is_hidden)। `answer` না থাকা পর্যন্ত storefront-এ দেখায় না — approve মানেই উত্তর দেওয়া। Spam হলে seller উত্তর না দিয়ে সরাসরি "Hide" করতে পারে।
- Seller dashboard-এ নতুন "Questions" ট্যাব (pending badge সহ), pending list-এ ইনলাইনে উত্তর লেখার ফর্ম।
- Storefront `_qna` partial (৮ template-এই `_reviews`-এর পরে) — answered question list + ask-form, একসাথে।

## ৮. Frequently bought together (2026-11-09) — শিপড

- `product_bundles` pure pivot টেবিল (product_id, companion_id, sort_order) — কোনো নতুন Eloquent model/store_id লাগেনি, দুই পাশই আগে থেকে store-scoped Product id।
- Seller product-edit পেজে checkbox দিয়ে অন্য product থেকে max ৪টা "companion" বেছে দেওয়া যায় (cross-store id হলে silently ignore হয়, variant-এর image_id check-এর মতোই)।
- Storefront-এ `_bundle` partial — প্রাইমারি product + companion-গুলো checkbox সহ (সব default checked), "Add selected to cart" একটা নতুন bulk endpoint (`/cart/add-bundle`, max ৫টা id, rate-limited) দিয়ে একসাথে cart-এ যুক্ত হয়।
- **V1 সীমাবদ্ধতা (ইচ্ছাকৃত):** bundle strip-এ কোনো variant picker নেই, তাই primary product বা কোনো companion-এর variant থাকলে সেটা bundle থেকে বাদ পড়ে (primary-তে variant থাকলে পুরো bundle section-ই hide হয়ে যায়) — ভুল variant-এ (বা variant ছাড়া) ভুল দামে কার্টে ঢুকে যাওয়া ঠেকানোর জন্য। চাইলে পরে variant-aware bundle বানানো যাবে, কিন্তু সেটা আলাদা বড় কাজ।

## ৯. স্কোপে নেই (deliberately, এই plan-এও)

- Fabricated urgency/social-proof ("X জন দেখছে") — সবসময়ই বাদ
- Multi-currency / bulk-MOQ pricing tier — B2C context-এ কম relevant
