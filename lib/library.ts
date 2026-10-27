"use client";

/**
 * Cart, Checkout & Order-Tracking library.
 * ALL cart reads/writes go through this file.
 * Cart data is stored in localStorage BASE64-ENCODED (never raw).
 */

import { useEffect, useState } from "react";
import { apiGet, apiPost } from "./fetcher";
import type {
  ApiOrder,
  SavedOrder,
  ApiOrderCreated,
  CartItem,
  CheckoutDetails,
  OrderStatus,
  OrderType,
  Product,
  SelectedModifier,
} from "./types";

const CART_KEY = "restaurant_cart_b64";
const ORDER_KEY = "restaurant_last_order_b64";
const ORDERS_KEY = "restaurant_orders_b64";
const CART_EVENT = "cart-updated";

/* ---------- encoding helpers ---------- */

export function encodeCart(data: unknown): string {
  return btoa(unescape(encodeURIComponent(JSON.stringify(data))));
}

export function decodeCart<T>(encoded: string): T | null {
  try {
    return JSON.parse(decodeURIComponent(escape(atob(encoded)))) as T;
  } catch {
    return null;
  }
}

/* ---------- internal: save + notify ---------- */

function saveCart(items: CartItem[]): void {
  localStorage.setItem(CART_KEY, encodeCart(items));
  window.dispatchEvent(new Event(CART_EVENT));
}

/* ---------- READ ---------- */

export function getCart(): CartItem[] {
  if (typeof window === "undefined") return [];
  const raw = localStorage.getItem(CART_KEY);
  if (!raw) return [];
  return decodeCart<CartItem[]>(raw) ?? [];
}

export function getCartCount(): number {
  return getCart().reduce((sum, item) => sum + item.qty, 0);
}

export function getCartTotal(): number {
  return getCart().reduce((sum, item) => sum + item.qty * item.unitPrice, 0);
}

/* ---------- price helpers ---------- */

/** The size group (e.g. Single / Medium / Full): its option price is the dish price, not an add-on */
function getSizeGroup(product: Product) {
  return (product.modifiers ?? []).find((g) => g.replacesPrice);
}

export function getUnitPrice(
  product: Product,
  modifiers: SelectedModifier[] = []
): number {
  const sizeGroup = getSizeGroup(product);
  const size = sizeGroup ? modifiers.find((m) => m.groupId === sizeGroup.id) : undefined;
  const base = size ? size.option.price : product.price;
  return base + modifiers.filter((m) => m !== size).reduce((s, m) => s + m.option.price, 0);
}

/** Price shown on the card: "From" the cheapest size when the dish has sizes */
export function getPriceLabel(product: Product): string {
  if (product.variants && product.variants.length > 0) return `From ${formatPrice(product.price)}`;
  const sizeGroup = getSizeGroup(product);
  if (!sizeGroup || sizeGroup.options.length === 0) return formatPrice(product.price);
  return `From ${formatPrice(Math.min(...sizeGroup.options.map((o) => o.price)))}`;
}

/** Unique cart-line id: same product + same modifier combo = same line. */
export function getCartLineId(
  productId: number,
  modifiers: SelectedModifier[] = []
): string {
  const sig = modifiers
    .map((m) => m.option.id)
    .sort((a, b) => a - b)
    .join(",");
  return `${productId}|${sig}`;
}

/* ---------- WRITE ---------- */

export function addToCart(
  product: Product,
  qty: number = 1,
  modifiers: SelectedModifier[] = []
): void {
  const cart = getCart();
  const id = getCartLineId(product.id, modifiers);
  const existing = cart.find((item) => item.id === id);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({ id, product, qty, unitPrice: getUnitPrice(product, modifiers), modifiers });
  }
  saveCart(cart);
}

export function updateCartQty(lineId: string, qty: number): void {
  let cart = getCart();
  if (qty <= 0) {
    cart = cart.filter((item) => item.id !== lineId);
  } else {
    const item = cart.find((i) => i.id === lineId);
    if (item) item.qty = qty;
  }
  saveCart(cart);
}

export function removeFromCart(lineId: string): void {
  saveCart(getCart().filter((item) => item.id !== lineId));
}

export function clearCart(): void {
  saveCart([]);
}

/* ---------- CHECKOUT ---------- */

export interface CheckoutResult {
  success: boolean;
  orderId: string | null;
  total: number | null;
  error?: string;
}

/** Sends the cart to the restaurant API. Prices (including modifiers) are computed on the server. */
export async function checkoutCart(
  details: CheckoutDetails & { type: OrderType; areaId?: number | null; branchId: number | null }
): Promise<CheckoutResult> {
  const cart = getCart();
  if (cart.length === 0) {
    return { success: false, orderId: null, total: null, error: "Your cart is empty." };
  }
  if (details.branchId === null) {
    return { success: false, orderId: null, total: null, error: "Please choose your branch first." };
  }

  try {
    const created = await apiPost<ApiOrderCreated>("/orders", {
      branch_id: details.branchId,
      type: details.type,
      area_id: details.type === "delivery" ? details.areaId ?? null : null,
      customer: { name: details.name, phone: details.phone, address: details.address },
      notes: details.notes ?? "",
      items: cart.map((item) =>
        item.product.dealId
          ? { deal_id: item.product.dealId, qty: item.qty }
          : { id: item.product.id, qty: item.qty, modifiers: item.modifiers.map((m) => m.option.id) }
      ),
    });

    const placedAt = new Date().toISOString();
    localStorage.setItem(ORDER_KEY, encodeCart({ ...created, placedAt }));
    saveOrder({
      orderId: created.orderId,
      type: created.type,
      branchName: created.branch.name,
      total: created.total,
      placedAt,
      items: cart.map((item) => ({
        name: item.product.name,
        qty: item.qty,
        modifiers: item.modifiers.map((m) => m.option.name),
      })),
    });
    clearCart();
    return { success: true, orderId: created.orderId, total: created.total };
  } catch (e) {
    return {
      success: false,
      orderId: null,
      total: null,
      error: e instanceof Error ? e.message : "Could not place the order. Please try again.",
    };
  }
}

export function getLastOrder<T>(): T | null {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem(ORDER_KEY);
  return raw ? decodeCart<T>(raw) : null;
}

/* ---------- MY ORDERS ----------
 * Orders placed from this browser, newest first (there are no customer accounts).
 * Their live status comes from GET /orders/{orderId}.
 */

export function getOrders(): SavedOrder[] {
  if (typeof window === "undefined") return [];
  const raw = localStorage.getItem(ORDERS_KEY);
  return (raw ? decodeCart<SavedOrder[]>(raw) : null) ?? [];
}

function saveOrder(order: SavedOrder): void {
  const orders = [order, ...getOrders().filter((o) => o.orderId !== order.orderId)].slice(0, 50);
  localStorage.setItem(ORDERS_KEY, encodeCart(orders));
}

/* ---------- ORDER TRACKING ----------
 * Polls GET /orders/{orderId} every `intervalMs` (default 5s).
 * Calls onStatus ONLY when the status actually changed.
 * Stops by itself at a final status (completed / cancelled).
 * Returns a stop() function to cancel polling.
 */

export const ORDER_STAGES: { key: OrderStatus; label: string; pickupLabel?: string; icon: string }[] = [
  { key: "received", label: "Order Received", icon: "📝" },
  { key: "preparing", label: "Preparing", icon: "👨‍🍳" },
  { key: "on_the_way", label: "On the Way", pickupLabel: "Ready for Pickup", icon: "🛵" },
  { key: "completed", label: "Completed", icon: "🎉" },
];

export function stageLabel(stage: (typeof ORDER_STAGES)[number], type: OrderType): string {
  return type === "pickup" && stage.pickupLabel ? stage.pickupLabel : stage.label;
}

export const FINAL_STATUSES: OrderStatus[] = ["completed", "cancelled"];

export function trackOrder(
  orderId: string,
  onStatus: (status: OrderStatus) => void,
  intervalMs: number = 5000
): () => void {
  let lastStatus: OrderStatus | null = null;
  let timer: ReturnType<typeof setInterval> | undefined;

  const stop = () => {
    if (timer) clearInterval(timer);
  };

  const check = async () => {
    try {
      const res = await apiGet<ApiOrder>(`/orders/${encodeURIComponent(orderId)}`);
      if (res.status !== lastStatus) {
        lastStatus = res.status;
        onStatus(res.status);
      }
      if (FINAL_STATUSES.includes(res.status)) stop();
    } catch {
      // Network or restaurant API hiccup: keep the last known status and try again next tick
    }
  };

  check(); // hit immediately, then every intervalMs
  timer = setInterval(check, intervalMs);

  return stop;
}

/* ---------- REACT HOOK ---------- */

export function useCart(): CartItem[] {
  const [items, setItems] = useState<CartItem[]>([]);

  useEffect(() => {
    const refresh = () => setItems(getCart());
    refresh();
    window.addEventListener(CART_EVENT, refresh);
    window.addEventListener("storage", refresh);
    return () => {
      window.removeEventListener(CART_EVENT, refresh);
      window.removeEventListener("storage", refresh);
    };
  }, []);

  return items;
}

/* ---------- misc ---------- */

/** Restaurant helpline, shown after placing an order and on cancelled orders */
export const COMPANY_PHONE = "+923131186728";
export const COMPANY_PHONE_DISPLAY = "+92 313 1186728";

/** Customer phone: +92 followed by exactly 10 digits (e.g. +923001234567) */
export const PHONE_PATTERN = /^\+92\d{10}$/;

export function isValidPhone(phone: string): boolean {
  return PHONE_PATTERN.test(phone);
}

export function formatPrice(price: number): string {
  return `Rs. ${price.toLocaleString("en-PK")}`;
}

export function isInCart(productId: number): boolean {
  return getCart().some((item) => item.product.id === productId);
}