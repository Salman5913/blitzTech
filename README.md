# Restaurant — Next.js + TypeScript + Tailwind

Food-ordering website connected to the restaurant ERP. The menu, categories, popular items, deals
and modifiers come from the restaurant API; orders are placed through it and tracked live.

## Structure

- `app/page.tsx` — home page, assembles all section components
- `app/checkout/page.tsx` — checkout flow
- `app/api/[...path]/route.ts` — same-origin proxy to the restaurant API (adds the API key on the server; only `menu`, `deals`, `areas`, `orders`, `health` are allowed)
- `components/` — one component per section:
  - `HeroSlider` (section 1), `PopularItems` (section 2), `DealsSection` (section 3),
    `CategorySection` (dynamic categories), `ProductCard` (used everywhere),
    `CategorySlider`, `SearchBar`, `Header`, `Footer`, `CartDrawer`, `CheckoutForm`, `OrderTracker`
- `lib/data.ts` — home page data: `/menu` from the API (popular = tags Popular / Bestseller / Most Ordered,
  deals = tag Deal). Hero slides and the sample menu are the fallback when the API is unreachable.
- `lib/fetcher.ts` — `backendRequest()` (server side, with the key) and `apiGet` / `apiPost` (browser, via the proxy)
- `lib/library.ts` — cart (localStorage, base64-encoded), checkout (`POST /orders`) and order tracking (`GET /orders/{id}`)
- `lib/types.ts` — product, cart and API types

## Connect to the restaurant API

The API lives in `D:\xampp\htdocs\demos3\restaurant_bridge\api\index.php` (part of the ERP) and reads the ERP database (`rs_` tables).

1. Start Apache and MySQL in XAMPP.
2. Check the API: http://localhost/demos3/restaurant_bridge/api/index.php?route=/health
3. Set the API URL and key in `.env.local` (already created).
   - `RESTAURANT_API_URL` — the API endpoint (default `http://localhost/demos3/restaurant_bridge/api/index.php`)
   - `RESTAURANT_API_KEY` — must equal `api_key` in `demos3/restaurant_bridge/api/config.php` (empty when the API has no key)
   - These variables are server-side only. Do not prefix them with `NEXT_PUBLIC_`.

## Run locally

```bash
npm install
npm run dev
# open http://localhost:3000
```

Restart `npm run dev` after changing `.env.local`.

## Modifiers

Modifier groups (e.g. Extras: Cheese) are set per menu item in the ERP database (`rs_modifier_groups`,
`rs_modifier_options`). The product modal shows them; the server computes all prices, so the browser never
sets a price.
