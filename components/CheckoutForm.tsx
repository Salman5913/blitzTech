"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import {
  useCart,
  formatPrice,
  checkoutCart,
  updateCartQty,
  removeFromCart,
  isValidPhone,
  COMPANY_PHONE,
  COMPANY_PHONE_DISPLAY,
} from "@/lib/library";
import Link from "next/link";
import { apiGet } from "@/lib/fetcher";
import { useBranch } from "@/lib/branch";
import type { ApiArea, OrderType } from "@/lib/types";
import OrderTracker from "./OrderTracker";

interface Placed {
  orderId: string;
  total: number;
  type: OrderType;
}

/** Delivery charge for an area at this subtotal (same rule as the restaurant API) */
function deliveryChargeFor(area: ApiArea, subtotal: number): number {
  if (area.freeOver !== null && subtotal >= area.freeOver) return 0;
  return area.charge;
}

export default function CheckoutForm() {
  const cart = useCart();
  const router = useRouter();
  const subtotal = cart.reduce((sum, item) => sum + item.qty * item.unitPrice, 0);
  const [form, setForm] = useState({ name: "", phone: "+92", address: "", notes: "" });
  const [type, setType] = useState<OrderType>("delivery");
  const [areas, setAreas] = useState<ApiArea[]>([]);
  const [areaId, setAreaId] = useState<number | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [placed, setPlaced] = useState<Placed | null>(null);
  const { branch } = useBranch();
  const branchId = branch?.id ?? null;

  // The chosen branch's delivery areas and their charges, as set in the ERP (reloaded when the branch changes)
  useEffect(() => {
    setAreaId(null);
    if (branchId === null) {
      setAreas([]);
      return;
    }
    apiGet<ApiArea[]>(`/branches/${branchId}/areas`)
      .then(setAreas)
      .catch(() => setAreas([]));
  }, [branchId]);

  const area = areas.find((a) => a.id === areaId) ?? null;
  const fee = type === "delivery" && area ? deliveryChargeFor(area, subtotal) : null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    if (!isValidPhone(form.phone)) {
      setError("Please enter a valid phone number: +92 followed by 10 digits (e.g. +923001234567).");
      return;
    }
    setSubmitting(true);
    const result = await checkoutCart({ ...form, type, areaId, branchId });
    setSubmitting(false);

    if (result.success && result.orderId !== null && result.total !== null) {
      setPlaced({ orderId: result.orderId, total: result.total, type });
    } else {
      setError(result.error ?? "Could not place the order. Please try again.");
    }
  };

  if (placed) {
    return (
      <div className="py-10">
        <div className="text-center mb-8">
          <p className="text-6xl mb-4">🎉</p>
          <h2 className="text-2xl font-extrabold">Order Placed!</h2>
          <p className="text-gray-500 mt-2">
            Total {formatPrice(placed.total)}. We will call you shortly to confirm.
          </p>
          <p className="text-sm text-gray-500 mt-3">
            Questions about your order? Call us at{" "}
            <a href={`tel:${COMPANY_PHONE}`} className="font-bold text-primary whitespace-nowrap">
              📞 {COMPANY_PHONE_DISPLAY}
            </a>
          </p>
        </div>
        <OrderTracker orderId={placed.orderId} type={placed.type} />
        <div className="text-center mt-10 flex flex-wrap justify-center gap-3">
          <Link
            href="/orders"
            className="border border-primary text-primary font-semibold px-6 py-3 rounded-full hover:bg-primary-light/50 transition"
          >
            My Orders
          </Link>
          <button
            onClick={() => router.push("/")}
            className="bg-primary text-white font-semibold px-6 py-3 rounded-full hover:bg-primary-dark transition"
          >
            Back to Home
          </button>
        </div>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="grid md:grid-cols-2 gap-8">
      {/* delivery details */}
      <div className="space-y-4">
        <h2 className="font-bold text-lg">Delivery Details</h2>

        <div className="grid grid-cols-2 gap-2">
          {(["delivery", "pickup"] as OrderType[]).map((option) => (
            <label
              key={option}
              className={`text-center border rounded-xl px-4 py-3 cursor-pointer transition ${
                type === option ? "border-primary bg-primary-light/50 font-semibold" : "border-gray-200"
              }`}
            >
              <input
                type="radio"
                name="order-type"
                value={option}
                checked={type === option}
                onChange={() => setType(option)}
                className="sr-only"
              />
              {option === "delivery" ? "🛵 Delivery" : "🛍️ Pickup"}
            </label>
          ))}
        </div>

        {/* the order goes to this branch; change it from the Branch tab under the header */}
        <p className="text-sm bg-white border border-gray-100 rounded-xl px-4 py-3">
          📍 {type === "pickup" ? "Pick up from" : "Ordering from"}{" "}
          <b>{branch ? branch.name : "— choose a branch —"}</b>
          {type === "pickup" && branch?.address && <span className="block text-xs text-gray-500 mt-0.5">{branch.address}</span>}
        </p>

        {error && (
          <p className="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{error}</p>
        )}
        <input
          required
          placeholder="Full name"
          value={form.name}
          onChange={(e) => setForm({ ...form, name: e.target.value })}
          className="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <div>
          <input
            required
            type="tel"
            inputMode="tel"
            placeholder="+923001234567"
            pattern="\+92[0-9]{10}"
            title="+92 followed by 10 digits, e.g. +923001234567"
            value={form.phone}
            onChange={(e) => {
              // Always starts with +92, then digits only (at most 10). A pasted 03xx... or 92... number is converted.
              const value = e.target.value;
              let digits = value.replace(/\D/g, "");
              if (value.startsWith("+92")) digits = digits.slice(2);
              else if (value.length < 3) digits = ""; // backspace into the +92 prefix
              else if (digits.startsWith("92")) digits = digits.slice(2);
              digits = digits.replace(/^0+/, "");
              setForm({ ...form, phone: "+92" + digits.slice(0, 10) });
            }}
            className={`w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary ${
              form.phone.length > 3 && !isValidPhone(form.phone) ? "border-red-400" : ""
            }`}
          />
          <p
            className={`text-xs mt-1 pl-1 ${
              form.phone.length > 3 && !isValidPhone(form.phone) ? "text-red-500" : "text-gray-400"
            }`}
          >
            Format: +92 followed by 10 digits (e.g. +923001234567)
          </p>
        </div>

        {type === "delivery" && (
          <>
            <select
              required
              value={areaId ?? ""}
              onChange={(e) => setAreaId(e.target.value ? Number(e.target.value) : null)}
              className="w-full border rounded-xl px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="">{areas.length ? "Select your area" : "No delivery areas yet"}</option>
              {areas.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.name} — {a.charge > 0 ? formatPrice(a.charge) : "free"}
                  {a.freeOver !== null ? ` (free over ${formatPrice(a.freeOver)})` : ""}
                </option>
              ))}
            </select>
            <textarea
              required
              placeholder="Delivery address"
              rows={3}
              value={form.address}
              onChange={(e) => setForm({ ...form, address: e.target.value })}
              className="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </>
        )}
        <textarea
          placeholder="Order notes (optional)"
          rows={2}
          value={form.notes}
          onChange={(e) => setForm({ ...form, notes: e.target.value })}
          className="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>

      {/* order summary */}
      <div>
        <h2 className="font-bold text-lg mb-4">Order Summary</h2>
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-3">
          {cart.length === 0 ? (
            <p className="text-gray-400 text-sm">Cart is empty.</p>
          ) : (
            cart.map((item) => (
              <div key={item.id} className="text-sm">
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="font-medium truncate">{item.product.name}</p>
                    <div className="flex items-center gap-2 text-gray-500">
                      <button type="button" onClick={() => updateCartQty(item.id, item.qty - 1)} className="hover:text-primary">−</button>
                      <span>× {item.qty}</span>
                      <button type="button" onClick={() => updateCartQty(item.id, item.qty + 1)} className="hover:text-primary">+</button>
                      <button type="button" onClick={() => removeFromCart(item.id)} className="hover:text-red-500 ml-1">🗑</button>
                    </div>
                  </div>
                  <span className="font-semibold whitespace-nowrap">
                    {formatPrice(item.unitPrice * item.qty)}
                  </span>
                </div>
                {item.modifiers.length > 0 && (
                  <p className="text-xs text-gray-400 mt-1 pl-1">
                    {item.modifiers.map((m) => m.option.name).join(", ")}
                  </p>
                )}
              </div>
            ))
          )}
          <div className="border-t pt-3 flex justify-between">
            <span>Subtotal</span>
            <span>{formatPrice(subtotal)}</span>
          </div>
          {type === "delivery" && (
            <div className="flex justify-between text-sm">
              <span>Delivery charge</span>
              <span>
                {fee === null ? "choose your area" : fee === 0 ? "Free" : formatPrice(fee)}
              </span>
            </div>
          )}
          <div className="border-t pt-3 flex justify-between font-extrabold">
            <span>Total</span>
            <span className="text-primary">{formatPrice(subtotal + (fee ?? 0))}</span>
          </div>
          <p className="text-xs text-gray-400">The final total is confirmed when the order is placed.</p>
        </div>
        <button
          type="submit"
          disabled={cart.length === 0 || submitting || branchId === null || !isValidPhone(form.phone) || (type === "delivery" && areaId === null)}
          className="mt-4 w-full bg-primary hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-full transition"
        >
          {submitting ? "Placing order…" : `Place Order — ${formatPrice(subtotal + (fee ?? 0))}`}
        </button>
      </div>
    </form>
  );
}
