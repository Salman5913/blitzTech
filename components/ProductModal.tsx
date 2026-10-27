"use client";

import Image from "next/image";
import type { ModifierGroup, Product, SelectedModifier } from "@/lib/types";
import { addToCart, formatPrice, getPriceLabel, getUnitPrice } from "@/lib/library";
import { useState } from "react";

interface Props {
  product: Product;
  onClose: () => void;
}

export default function ProductModal({ product, onClose }: Props) {
  const [qty, setQty] = useState(1);
  // groupId -> selected option ids
  const [selected, setSelected] = useState<Record<number, number[]>>({});
  // the chosen attribute (variant) of a stock item, e.g. Medium
  const [variantId, setVariantId] = useState<number | null>(null);

  const toggleOption = (group: ModifierGroup, optionId: number) => {
    setSelected((prev) => {
      const current = prev[group.id] ?? [];
      if (group.type === "single") {
        return { ...prev, [group.id]: [optionId] }; // radio behaviour
      }
      // checkbox behaviour
      return {
        ...prev,
        [group.id]: current.includes(optionId)
          ? current.filter((id) => id !== optionId)
          : [...current, optionId],
      };
    });
  };

  const buildModifiers = (): SelectedModifier[] => {
    const out: SelectedModifier[] = [];
    for (const group of product.modifiers ?? []) {
      for (const optionId of selected[group.id] ?? []) {
        const option = group.options.find((o) => o.id === optionId);
        if (option) {
          out.push({ groupId: group.id, groupName: group.name, option });
        }
      }
    }
    return out;
  };

  const modifiers = buildModifiers();
  const variant = product.variants?.find((v) => v.id === variantId) ?? null;
  const variantMissing = (product.variants?.length ?? 0) > 0 && !variant;
  const unitPrice = getUnitPrice(variant ? { ...product, price: variant.price } : product, modifiers);
  const sizeGroup = (product.modifiers ?? []).find((g) => g.replacesPrice);
  const sizeChosen = !!sizeGroup && (selected[sizeGroup.id] ?? []).length > 0;

  const missingRequired = (product.modifiers ?? [])
    .filter((g) => g.required && (selected[g.id] ?? []).length === 0)
    .map((g) => g.name);

  const canAdd = missingRequired.length === 0 && !variantMissing;

  const handleAdd = () => {
    if (!canAdd) return;
    if (variant) {
      // the chosen attribute is the dish that is ordered (its own ERP dish and stock)
      addToCart({ ...product, id: variant.id, name: `${product.name} · ${variant.name}`, price: variant.price, variants: undefined }, qty, modifiers);
    } else {
      addToCart(product, qty, modifiers);
    }
    onClose();
  };

  const share = async () => {
    const url = `${window.location.origin}/?product=${product.id}`;
    if (navigator.share) {
      await navigator.share({ title: product.name, url }).catch(() => {});
    } else {
      await navigator.clipboard.writeText(url);
      alert("Link copied!");
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* overlay */}
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />

      {/* modal card */}
      <div className="relative z-10 w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden grid md:grid-cols-2 max-h-[90vh]">
        {/* LEFT — image + name overlay */}
        <div className="relative h-56 md:h-auto md:min-h-[540px]">
          <Image src={product.image} alt={product.name} fill className="object-cover" />
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
          <div className="absolute bottom-0 left-0 right-0 p-6">
            <h2 className="text-white text-2xl md:text-3xl font-extrabold">{product.name}</h2>
            <p className="text-white/80 text-sm mt-1">{product.description}</p>
          </div>
        </div>

        {/* RIGHT — price, attributes, add to cart */}
        {/* on phones the image sits above (h-56 = 14rem), so this column gets the rest of the 90vh */}
        <div className="flex flex-col max-h-[calc(90vh-14rem)] md:max-h-[90vh]">
          <div className="flex items-start justify-between p-6 pb-0">
            <p className="text-2xl md:text-3xl font-extrabold">
              {(sizeGroup && !sizeChosen) || variantMissing ? getPriceLabel(product) : formatPrice(unitPrice)}
            </p>
            <div className="flex gap-2">
              <button
                onClick={share}
                aria-label="Share"
                className="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center hover:bg-primary-dark transition"
              >
                🔗
              </button>
              <button
                onClick={onClose}
                aria-label="Close"
                className="w-10 h-10 rounded-full bg-primary text-white text-xl flex items-center justify-center hover:bg-primary-dark transition"
              >
                ✕
              </button>
            </div>
          </div>

          {/* scrollable attributes */}
          <div className="flex-1 overflow-y-auto p-6 space-y-6">
            {/* attributes of the stock item (e.g. Medium, Full), each with its own price */}
            {product.variants && product.variants.length > 0 && (
              <div className="border border-gray-100 rounded-2xl p-4">
                <div className="flex items-center justify-between mb-3">
                  <h3 className="font-bold">Choose Size</h3>
                  <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-light text-primary">ⓘ Required</span>
                </div>
                <div className="space-y-2">
                  {product.variants.map((v) => (
                    <label
                      key={v.id}
                      className={`flex items-center justify-between gap-3 border rounded-xl px-4 py-3 transition ${
                        !v.available ? "opacity-40 cursor-not-allowed" : "cursor-pointer"
                      } ${variant?.id === v.id ? "border-primary bg-primary-light/50" : "border-gray-100 hover:border-gray-300"}`}
                    >
                      <span className="flex items-center gap-3">
                        <input
                          type="radio"
                          name="variant"
                          className="w-4 h-4 accent-[#D72631]"
                          disabled={!v.available}
                          checked={variant?.id === v.id}
                          onChange={() => setVariantId(v.id)}
                        />
                        <span className="text-sm font-medium">
                          {v.name}
                          {!v.available && " (sold out)"}
                        </span>
                      </span>
                      <span className="text-sm font-semibold text-gray-500">{formatPrice(v.price)}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}
            {(product.modifiers ?? []).map((group) => (
              <div key={group.id} className="border border-gray-100 rounded-2xl p-4">
                <div className="flex items-center justify-between mb-3">
                  <h3 className="font-bold">{group.name}</h3>
                  <span
                    className={`text-xs font-semibold px-2.5 py-1 rounded-full ${
                      group.required
                        ? "bg-primary-light text-primary"
                        : "bg-gray-100 text-gray-500"
                    }`}
                  >
                    {group.required ? "ⓘ Required" : "Optional"}
                  </span>
                </div>

                {group.required && (selected[group.id] ?? []).length === 0 && (
                  <p className="text-xs text-primary mb-2">Please select 1 to continue</p>
                )}

                <div className="space-y-2">
                  {group.options.map((option) => {
                    const isChecked = (selected[group.id] ?? []).includes(option.id);
                    return (
                      <label
                        key={option.id}
                        className={`flex items-center justify-between gap-3 border rounded-xl px-4 py-3 cursor-pointer transition ${
                          isChecked
                            ? "border-primary bg-primary-light/50"
                            : "border-gray-100 hover:border-gray-300"
                        }`}
                      >
                        <span className="flex items-center gap-3">
                          <input
                            type={group.type === "single" ? "radio" : "checkbox"}
                            name={`group-${group.id}`}
                            checked={isChecked}
                            onChange={() => toggleOption(group, option.id)}
                            className="w-4 h-4 accent-[#D72631]"
                          />
                          <span className="text-sm font-medium">{option.name}</span>
                        </span>
                        {group.replacesPrice ? (
                          <span className="text-sm font-semibold text-gray-500">
                            {formatPrice(option.price)}
                          </span>
                        ) : (
                          option.price > 0 && (
                            <span className="text-sm font-semibold text-gray-500">
                              +{formatPrice(option.price)}
                            </span>
                          )
                        )}
                      </label>
                    );
                  })}
                </div>
              </div>
            ))}

            {(!product.modifiers || product.modifiers.length === 0) && !product.variants?.length && (
              <p className="text-sm text-gray-400">No customization available for this item.</p>
            )}
          </div>

          {/* bottom bar — qty + add */}
          <div className="border-t p-4 flex items-center gap-3">
            <div className="flex items-center border border-gray-200 rounded-full">
              <button
                onClick={() => setQty((q) => Math.max(1, q - 1))}
                className="px-3 py-2 text-gray-500 hover:text-primary"
              >
                −
              </button>
              <span className="w-6 text-center text-sm font-bold">{qty}</span>
              <button
                onClick={() => setQty((q) => q + 1)}
                className="px-3 py-2 text-gray-500 hover:text-primary"
              >
                +
              </button>
            </div>
            <button
              onClick={handleAdd}
              disabled={!canAdd}
              className="flex-1 bg-primary hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-3 rounded-full transition"
            >
              {formatPrice(unitPrice * qty)} | Add to Cart →
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}