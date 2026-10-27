export interface ModifierOption {
  id: number;
  name: string;
  price: number; // 0 = free
}

export interface ModifierGroup {
  id: number;
  name: string;
  type: "single" | "multiple"; // single = radio, multiple = checkbox
  required: boolean;
  replacesPrice?: boolean; // size group: the chosen option's price is the dish price (not an add-on)
  options: ModifierOption[];
}

export interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  tag?: string;
  available?: boolean; // false = sold out (shown, can't be ordered)
  modifiers?: ModifierGroup[];
  dealId?: number; // set for a deal (combo): ordered as deal_id, not as a dish
  variants?: ProductVariant[]; // one ERP stock item with attributes (e.g. Medium / Full); each is its own dish
}

export interface ProductVariant {
  id: number; // the ERP dish that is ordered
  name: string; // e.g. Medium
  price: number;
  available: boolean;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  image: string;
  products: Product[];
}

export interface HeroSlide {
  id: number;
  title: string;
  subtitle: string;
  image: string;
  ctaText: string;
}

export interface HomePageData {
  heroSlides: HeroSlide[];
  popularItems: Product[];
  deals: Product[];
  categories: Category[];
}

export interface SelectedModifier {
  groupId: number;
  groupName: string;
  option: ModifierOption;
}

export interface CartItem {
  id: string; // product.id + modifiers signature (same combo = same cart line)
  product: Product;
  qty: number;
  unitPrice: number; // product.price + selected modifier prices
  modifiers: SelectedModifier[];
}

export interface CheckoutDetails {
  name: string;
  phone: string;
  address: string;
  notes?: string;
}

export type OrderType = "delivery" | "pickup";

// Same values the restaurant API and the ERP use
export type OrderStatus = "received" | "preparing" | "on_the_way" | "completed" | "cancelled";

/* ---------- restaurant API (demos3/restaurant_bridge/api/index.php) ---------- */

export interface ApiMenuItem {
  id: number;
  name: string;
  description: string;
  price: number;
  category: string;
  emoji: string;
  image: string | null;
  tag: string | null;
  available: boolean;
  hero: boolean; // "Show on hero slider" in the ERP
  stockId: number | null; // ERP stock item this dish is linked to
  stockName: string | null;
  variant: string | null; // attribute name when the dish is one attribute of a stock item
  modifiers: ModifierGroup[];
}

export interface ApiDeal {
  id: number;
  name: string;
  description: string;
  price: number;
  normalPrice: number;
  savings: number;
  items: { id: number; name: string; price: number }[];
}

/** Cookie with the chosen branch id (set in lib/branch.ts, read by the home page on the server) */
export const BRANCH_COOKIE = "restaurant_branch";

/** A branch customers can order from; every branch has its own menu, deals and delivery areas */
export interface ApiBranch {
  id: number;
  name: string;
  address: string;
  phone: string;
}

export interface ApiArea {
  id: number;
  name: string;
  charge: number; // delivery charge for this area
  freeOver: number | null; // free delivery from this subtotal (null = never free)
}

export interface ApiOrderCreated {
  orderId: string;
  status: OrderStatus;
  type: OrderType;
  branch: { id: number; name: string };
  total: number;
  createdAt: string;
}

/** An order placed from this browser, kept in localStorage for "My Orders" */
export interface SavedOrder {
  orderId: string;
  type: OrderType;
  branchName?: string; // orders saved before branches existed have none
  total: number;
  placedAt: string;
  items: { name: string; qty: number; modifiers: string[] }[];
}

export interface ApiOrder {
  orderId: string;
  status: OrderStatus;
  type: OrderType;
  branch: { id: number; name: string };
  total: number;
  customer: { name: string; phone: string; address: string };
  createdAt: string;
}
