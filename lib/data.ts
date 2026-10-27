import type { ApiBranch, ApiDeal, ApiMenuItem, Category, HeroSlide, HomePageData, Product } from "./types";
import { backendRequest } from "./fetcher";

const U = (id: string) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&w=800&q=80`;

const FALLBACK_IMAGE = U("photo-1504674900247-0877df9cc836");

// Tags set in the ERP (rs_menu_items.tag) that place a dish in a home-page section
const POPULAR_TAGS = ["Popular", "Bestseller", "Most Ordered"];

// Deal ids live in their own range, so a deal never shares a cart line id with a dish
const DEAL_ID_OFFSET = 100000;

function toDeal(deal: ApiDeal): Product {
  const dishes = deal.items.map((item) => item.name).join(" + ");
  const saving = deal.savings > 0 ? ` · You save Rs. ${deal.savings.toLocaleString("en-PK")}` : "";
  return {
    id: DEAL_ID_OFFSET + deal.id,
    dealId: deal.id,
    name: deal.name,
    description: `${dishes}${saving}`,
    price: deal.price,
    image: FALLBACK_IMAGE,
    tag: deal.savings > 0 ? `Save ${Math.round((deal.savings / deal.normalPrice) * 100)}%` : undefined,
    available: true,
  };
}

function toProduct(row: ApiMenuItem): Product {
  return {
    id: row.id,
    name: row.name,
    description: row.description,
    price: row.price,
    image: row.image || FALLBACK_IMAGE,
    tag: row.tag || undefined,
    available: row.available,
    modifiers: row.modifiers,
  };
}

/**
 * Rows of one ERP stock item (e.g. "white chicken biryani" with attributes Medium and Full) become ONE product
 * with variants, so the website shows one card and the modal lists the attributes with their prices.
 */
function groupRows(rows: ApiMenuItem[]): { row: ApiMenuItem; product: Product }[] {
  const entries: { row: ApiMenuItem; product: Product }[] = [];
  const byStock = new Map<number, { row: ApiMenuItem; product: Product }>();

  for (const row of rows) {
    if (row.stockId === null) {
      entries.push({ row, product: toProduct(row) });
      continue;
    }
    let entry = byStock.get(row.stockId);
    if (!entry) {
      entry = { row, product: toProduct(row) };
      byStock.set(row.stockId, entry);
      entries.push(entry);
    }
    entry.product.variants = [
      ...(entry.product.variants ?? []),
      { id: row.id, name: row.variant || row.name, price: row.price, available: row.available },
    ];
  }

  for (const entry of entries) {
    const variants = entry.product.variants;
    if (!variants) continue;
    if (variants.length === 1) {
      // only one attribute is on the website: a plain dish
      delete entry.product.variants;
      continue;
    }
    const title = (entry.row.stockName || entry.row.name).replace(/\b\w/g, (c) => c.toUpperCase());
    entry.product = {
      ...entry.product,
      name: title,
      price: Math.min(...variants.map((v) => v.price)),
      available: variants.some((v) => v.available),
    };
  }

  return entries;
}

const EMPTY_HOME: HomePageData = { heroSlides: [], popularItems: [], deals: [], categories: [] };

/** Branches the website offers (ERP: Restaurant > Branches). Empty when the restaurant API is unreachable. */
export async function getBranches(): Promise<ApiBranch[]> {
  try {
    return await backendRequest<ApiBranch[]>("/branches");
  } catch {
    return [];
  }
}

/**
 * Home page data from the chosen branch's ERP menu (GET /branches/{id}/menu). Every branch has its own menu and deals.
 * No branch chosen yet = an empty page behind the branch picker.
 * Hero slides are the dishes marked "Show on hero slider" in the ERP (and available to order).
 * If the restaurant API is unreachable, the sample data is returned so the site still renders.
 */
export async function getHomePageData(branchId: number | null): Promise<HomePageData> {
  if (branchId === null) return EMPTY_HOME;

  let rows: ApiMenuItem[];
  try {
    rows = await backendRequest<ApiMenuItem[]>(`/branches/${branchId}/menu`);
  } catch {
    return getSampleHomePageData();
  }

  const entries = groupRows(rows);
  const products = entries.map((e) => e.product);

  // Deals are their own list (ERP "Deals" tab, shown on the website). A deals failure keeps the menu.
  let deals: Product[] = [];
  try {
    deals = (await backendRequest<ApiDeal[]>(`/branches/${branchId}/deals`)).map(toDeal);
  } catch {
    deals = [];
  }

  // Only dishes the ERP marks for the hero slider, and that can be ordered. No flagged dish = no hero.
  const heroSlides: HeroSlide[] = entries
    .filter((e) => e.row.hero && e.product.available)
    .map((e) => ({
      id: e.product.id,
      title: e.product.name,
      subtitle: e.product.description,
      image: e.product.image,
      ctaText: "Order Now",
    }));

  const byTag = (tags: string[]) => products.filter((p) => p.tag && tags.includes(p.tag));

  // Categories keep the ERP's menu order (first appearance of each category)
  const grouped = new Map<string, Product[]>();
  entries.forEach(({ row, product }) => {
    const list = grouped.get(row.category) ?? [];
    list.push(product);
    grouped.set(row.category, list);
  });
  const categories: Category[] = Array.from(grouped.entries()).map(([name, items], i) => ({
    id: i + 1,
    name,
    slug: name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, ""),
    image: items[0].image,
    products: items,
  }));

  return {
    heroSlides,
    popularItems: byTag(POPULAR_TAGS),
    deals,
    categories,
  };
}

async function getSampleHomePageData(): Promise<HomePageData> {
  // ---- FIRST SECTION: dynamic hero slider ----
  const heroSlides = [
    {
      id: 1,
      title: "Handcrafted Desi BBQ",
      subtitle: "Charcoal grilled, marinated overnight in our secret spices.",
      image: U("photo-1555939594-58d7cb561ad1"),
      ctaText: "Order Now",
    },
    {
      id: 2,
      title: "Stone-Baked Pizzas",
      subtitle: "Fresh dough, real mozzarella, fired at 400 degrees.",
      image: U("photo-1513104890138-7c749659a591"),
      ctaText: "Explore Pizzas",
    },
    {
      id: 3,
      title: "Royal Chicken Biryani",
      subtitle: "Aged basmati layered with saffron and slow-cooked chicken.",
      image: U("photo-1631452180519-c014fe946bc7"),
      ctaText: "Try Biryani",
    },
  ];

  // ---- SECOND SECTION: popular items (with dummy attributes) ----
  const popularItems: Product[] = [
    {
      id: 101,
      name: "Chicken Tikka Pizza",
      description: "Tikka chunks, onions, mozzarella on a stone-baked crust.",
      price: 1299,
      image: U("photo-1513104890138-7c749659a591"),
      tag: "Bestseller",
      modifiers: [
        {
          id: 1,
          name: "Choose Size",
          type: "single",
          required: true,
          options: [
            { id: 11, name: "Small (8\")", price: 0 },
            { id: 12, name: "Medium (10\")", price: 300 },
            { id: 13, name: "Large (12\")", price: 600 },
          ],
        },
        {
          id: 2,
          name: "Extras",
          type: "multiple",
          required: false,
          options: [
            { id: 14, name: "Extra Cheese", price: 200 },
            { id: 15, name: "Stuffed Crust", price: 250 },
            { id: 16, name: "Jalapeños", price: 100 },
          ],
        },
      ],
    },
    {
      id: 102,
      name: "Special Chicken Biryani",
      description: "Fragrant basmati, spicy masala, served with raita.",
      price: 549,
      image: U("photo-1631452180519-c014fe946bc7"),
      tag: "Most Ordered",
      modifiers: [
        {
          id: 3,
          name: "Extras",
          type: "multiple",
          required: false,
          options: [
            { id: 17, name: "Chicken Piece", price: 180 },
            { id: 18, name: "Extra Raita", price: 50 },
            { id: 19, name: "Extra Rice", price: 150 },
          ],
        },
      ],
    },
    {
      id: 103,
      name: "Zinger Burger",
      description: "Crispy fillet, signature sauce, toasted brioche bun.",
      price: 649,
      image: U("photo-1568901346375-23c9450c58cd"),
      tag: "Fan Favorite",
      modifiers: [
        {
          id: 4,
          name: "Extras",
          type: "multiple",
          required: false,
          options: [
            { id: 20, name: "Cheese Slice", price: 100 },
            { id: 21, name: "Extra Zinger Patty", price: 250 },
            { id: 22, name: "Fries Upgrade", price: 150 },
          ],
        },
      ],
    },
    {
      id: 104,
      name: "Malai Boti Platter",
      description: "Creamy grilled cubes with naan and mint chutney.",
      price: 1199,
      image: U("photo-1603894584373-5ac82b2ae398"),
      tag: "Chef Special",
    },
  ];

  // ---- THIRD SECTION: deals (with required drink choice, like your screenshot) ----
  const deals: Product[] = [
    {
      id: 201,
      name: "Family Feast Deal",
      description: "2 large pizzas, 1.5L drink and garlic bread. Serves 4.",
      price: 2999,
      image: U("photo-1565299624946-b28f40a0ae38"),
      tag: "Save 25%",
      modifiers: [
        {
          id: 5,
          name: "Choose Your Drink",
          type: "single",
          required: true,
          options: [
            { id: 23, name: "Pepsi 1.5L", price: 0 },
            { id: 24, name: "7up 1.5L", price: 0 },
            { id: 25, name: "Mirinda 1.5L", price: 0 },
          ],
        },
        {
          id: 6,
          name: "Extras",
          type: "multiple",
          required: false,
          options: [
            { id: 26, name: "Cheese Dip", price: 150 },
            { id: 27, name: "Garlic Bread Upgrade", price: 200 },
          ],
        },
      ],
    },
    {
      id: 202,
      name: "Burger Duo Deal",
      description: "2 zinger burgers + large fries + 2 drinks.",
      price: 1499,
      image: U("photo-1571091718767-18b5b1457add"),
      tag: "Save 20%",
      modifiers: [
        {
          id: 7,
          name: "Choose Your Drink",
          type: "single",
          required: true,
          options: [
            { id: 28, name: "Buddy Pack White Soft Drink", price: 0 },
            { id: 29, name: "Buddy Pack Black Soft Drink", price: 0 },
            { id: 30, name: "Buddy Pack Orange Soft Drink", price: 0 },
          ],
        },
      ],
    },
    {
      id: 203,
      name: "BBQ Night Deal",
      description: "Malai boti, seekh kebabs, 4 naans and raita.",
      price: 2499,
      image: U("photo-1555939594-58d7cb561ad1"),
      tag: "Weekend Only",
    },
  ];

  // ---- DYNAMIC CATEGORIES + PRODUCTS ----
  const categories = [
    {
      id: 1,
      name: "Pizzas",
      slug: "pizzas",
      image: U("photo-1513104890138-7c749659a591"),
      products: [
        {
          id: 301, name: "Chicken Tikka Pizza", description: "Spicy tikka, onions, mozzarella.",
          price: 1299, image: U("photo-1513104890138-7c749659a591"),
          modifiers: popularItems[0].modifiers,
        },
        { id: 302, name: "Fajita Pizza", description: "Smoky fajita chicken, capsicum, olives.", price: 1399, image: U("photo-1565299624946-b28f40a0ae38") },
        { id: 303, name: "Cheese Lovers", description: "Triple cheese blend overload.", price: 1199, image: U("photo-1574071318508-1cdbab80d002") },
        { id: 304, name: "Veggie Supreme", description: "Mushrooms, corn, peppers, onions.", price: 1099, image: U("photo-1571407970349-bc81e7e96d47") },
      ] as Product[],
    },
    {
      id: 2,
      name: "Burgers",
      slug: "burgers",
      image: U("photo-1568901346375-23c9450c58cd"),
      products: [
        {
          id: 401, name: "Zinger Burger", description: "Crispy spicy fillet with signature sauce.",
          price: 649, image: U("photo-1568901346375-23c9450c58cd"),
          modifiers: popularItems[2].modifiers,
        },
        { id: 402, name: "Beef Smash Burger", description: "Double smashed patty, cheddar, pickles.", price: 899, image: U("photo-1553979459-d2229ba7433b") },
        { id: 403, name: "Chicken Patty Melt", description: "Grilled patty, caramelized onions, cheese.", price: 749, image: U("photo-1550547660-d9450f859349") },
        { id: 404, name: "Loaded Fries", description: "Crispy fries topped with cheese sauce.", price: 499, image: U("photo-1573080496219-bb080dd4f877") },
      ] as Product[],
    },
    {
      id: 3,
      name: "BBQ & Grills",
      slug: "bbq-grills",
      image: U("photo-1555939594-58d7cb561ad1"),
      products: [
        { id: 501, name: "Malai Boti", description: "Creamy marinated grilled chicken cubes.", price: 1199, image: U("photo-1603894584373-5ac82b2ae398") },
        { id: 502, name: "Seekh Kebab (4 pc)", description: "Hand-rolled minced beef kebabs.", price: 799, image: U("photo-1555939594-58d7cb561ad1") },
        { id: 503, name: "BBQ Platter", description: "Assorted grills with naan and chutneys.", price: 2299, image: U("photo-1544025162-d76694265947") },
        { id: 504, name: "Grilled Wings (6 pc)", description: "Charcoal wings in peri peri glaze.", price: 899, image: U("photo-1527477396000-e27163b481c2") },
      ] as Product[],
    },
    {
      id: 4,
      name: "Desserts",
      slug: "desserts",
      image: U("photo-1551024506-0bccd828d307"),
      products: [
        { id: 601, name: "Molten Lava Cake", description: "Warm chocolate cake with a gooey center.", price: 449, image: U("photo-1551024506-0bccd828d307") },
        { id: 602, name: "Gulab Jamun (4 pc)", description: "Soft khoya dumplings in rose syrup.", price: 349, image: U("photo-1551024506-0bccd828d307") },
        { id: 603, name: "Cheesecake Slice", description: "Baked New York style with berry compote.", price: 549, image: U("photo-1533134242443-d4fd215305ad") },
        { id: 604, name: "Kulfi Falooda", description: "Traditional kulfi with vermicelli and basil seeds.", price: 399, image: U("photo-1563805042-7684c019e1cb") },
      ] as Product[],
    },
  ];

  return { heroSlides, popularItems, deals, categories };
}

/** Helper: flatten every product across all categories (used by search). */
export function flattenProducts(data: HomePageData): Product[] {
  return data.categories.flatMap((c) => c.products);
}