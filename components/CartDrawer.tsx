"use client";

import Link from "next/link";
import type { CartItem } from "@/lib/types";
import {
  formatPrice,
  updateCartQty,
  removeFromCart,
} from "@/lib/library";

interface Props {
  open: boolean;
  onClose: () => void;
  items: CartItem[];
}

export default function CartDrawer({ open, onClose, items }: Props) {
  const total = items.reduce((sum, item) => sum + item.qty * item.unitPrice, 0);

  return (
    <>
      <div
        onClick={onClose}
        className={`fixed inset-0 bg-black/40 z-40 transition-opacity ${
          open ? "opacity-100" : "opacity-0 pointer-events-none"
        }`}
      />
      <aside
        className={`fixed top-0 right-0 h-full w-full max-w-md bg-white z-50 transform transition-transform duration-300 flex flex-col ${
          open ? "translate-x-0 shadow-2xl" : "translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between p-4 border-b">
          <h2 className="text-lg font-bold">Your Cart</h2>
          <button
            onClick={onClose}
            aria-label="Close cart"
            className="w-10 h-10 -mr-2 rounded-full flex items-center justify-center text-gray-400 hover:text-ink hover:bg-gray-100 text-xl transition"
          >
            ✕
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {items.length === 0 ? (
            <p className="text-gray-400 text-center mt-10">
              Your cart is empty. Add something tasty! 🍔
            </p>
          ) : (
            items.map((item) => (
              <div key={item.id} className="flex gap-3 items-center border rounded-xl p-3">
                <div className="w-16 h-16 rounded-lg overflow-hidden relative flex-shrink-0 bg-gray-100">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={item.product.image} alt={item.product.name} className="object-cover w-full h-full" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-semibold text-sm truncate">{item.product.name}</p>
                  {item.modifiers.length > 0 && (
                    <p className="text-xs text-gray-400 truncate">
                      {item.modifiers.map((m) => m.option.name).join(", ")}
                    </p>
                  )}
                  <p className="text-primary font-bold text-sm">
                    {formatPrice(item.unitPrice)}
                  </p>
                  <div className="flex items-center gap-2 mt-1">
                    <button
                      onClick={() => updateCartQty(item.id, item.qty - 1)}
                      className="w-6 h-6 border rounded-full text-sm hover:border-primary hover:text-primary"
                    >
                      −
                    </button>
                    <span className="text-sm font-semibold w-5 text-center">{item.qty}</span>
                    <button
                      onClick={() => updateCartQty(item.id, item.qty + 1)}
                      className="w-6 h-6 border rounded-full text-sm hover:border-primary hover:text-primary"
                    >
                      +
                    </button>
                  </div>
                </div>
                <button
                  onClick={() => removeFromCart(item.id)}
                  className="text-gray-300 hover:text-red-500 text-lg"
                  aria-label="Remove item"
                >
                  🗑
                </button>
              </div>
            ))
          )}
        </div>

        {items.length > 0 && (
          <div className="border-t p-4 space-y-3">
            <div className="flex justify-between font-bold text-lg">
              <span>Subtotal</span>
              <span className="text-primary">{formatPrice(total)}</span>
            </div>
            <Link
              href="/checkout"
              onClick={onClose}
              className="block text-center bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-full transition"
            >
              Proceed to Checkout →
            </Link>
          </div>
        )}
      </aside>
    </>
  );
}