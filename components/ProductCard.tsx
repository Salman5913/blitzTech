"use client";

import Image from "next/image";
import type { Product } from "@/lib/types";
import {
  addToCart,
  getPriceLabel,
  useCart,
  getCartLineId,
  updateCartQty,
} from "@/lib/library";
import { useState } from "react";
import ProductModal from "./ProductModal";

export default function ProductCard({ product }: { product: Product }) {
  const cart = useCart();
  const [modalOpen, setModalOpen] = useState(false);

  // products with required attributes (e.g. "Choose Your Drink") must go through the modal
  const hasRequiredModifiers =
    (product.modifiers ?? []).some((g) => g.required) || (product.variants?.length ?? 0) > 0;

  const handleQuickAdd = (e: React.MouseEvent) => {
    e.stopPropagation(); // do not open the modal when clicking Add
    if (hasRequiredModifiers) {
      setModalOpen(true);
    } else {
      addToCart(product, 1);
    }
  };

  // simple product's cart line (no modifiers) drives the − / + stepper
  const lineId = getCartLineId(product.id);
  const line = cart.find((item) => item.id === lineId);
  // any line of this product (incl. modifier combos) marks the card as added
  const added = cart.some((item) => item.product.id === product.id);

  return (
    <>
      {/* clicking anywhere on the card opens the modal */}
      <div
        onClick={() => setModalOpen(true)}
        className="bg-white rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden flex flex-col cursor-pointer"
      >
        <div className="relative h-44 w-full">
          <Image
            src={product.image}
            alt={product.name}
            fill
            className="object-cover"
          />
          {product.tag && (
            <span className="absolute top-3 left-3 bg-primary text-white text-xs font-bold px-2.5 py-1 rounded-full">
              {product.tag}
            </span>
          )}
        </div>
        <div className="p-4 flex flex-col flex-1">
          <h3 className="font-bold text-base leading-snug">{product.name}</h3>
          <p className="text-sm text-gray-500 mt-1 line-clamp-2 flex-1">
            {product.description}
          </p>
          <p className="mt-2 font-extrabold text-primary">
            {getPriceLabel(product)}
          </p>

          {/* stopPropagation so these buttons don't trigger the card's onClick */}
          <div
            className="mt-3 flex flex-wrap items-center gap-2"
            onClick={(e) => e.stopPropagation()}
          >
            {/* − / + only shows once the item is in the cart */}
            {line && (
              <div className="flex items-center justify-between border border-gray-200 rounded-full w-full sm:w-auto">
                <button
                  onClick={() => updateCartQty(lineId, line.qty - 1)}
                  className="px-3 py-1.5 text-gray-500 hover:text-primary"
                  aria-label="Decrease quantity"
                >
                  −
                </button>
                <span className="w-6 text-center text-sm font-semibold">{line.qty}</span>
                <button
                  onClick={() => updateCartQty(lineId, line.qty + 1)}
                  className="px-3 py-1.5 text-gray-500 hover:text-primary"
                  aria-label="Increase quantity"
                >
                  +
                </button>
              </div>
            )}
            <button
              onClick={handleQuickAdd}
              disabled={product.available === false}
              className={`flex-1 min-w-0 whitespace-nowrap flex items-center justify-center gap-1.5 text-white disabled:opacity-40 disabled:cursor-not-allowed text-sm font-semibold px-4 py-2 rounded-full transition ${
                added ? "bg-green-600 hover:bg-green-700" : "bg-ink hover:bg-primary"
              }`}
            >
              {product.available === false ? (
                "Sold out"
              ) : added ? (
                <>
                  <svg
                    className="w-4 h-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={3}
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  Added
                </>
              ) : (
                "Add to Cart"
              )}
            </button>
          </div>
        </div>
      </div>

      {/* popup modal with radio/checkbox attributes */}
      {modalOpen && (
        <ProductModal product={product} onClose={() => setModalOpen(false)} />
      )}
    </>
  );
}