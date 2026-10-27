"use client";

import type { Product } from "@/lib/types";
import { useMemo, useState } from "react";
import ProductCard from "./ProductCard";

export default function SearchBar({ products }: { products: Product[] }) {
  const [query, setQuery] = useState("");

  const results = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    return products.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.description.toLowerCase().includes(q)
    );
  }, [query, products]);

  return (
    <section className="mx-auto max-w-7xl px-4 mt-8">
      <div className="relative">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search dishes… e.g. biryani, pizza, burger"
          className="w-full rounded-full border border-gray-200 bg-white px-5 py-3.5 pl-12 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <span className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        {query && (
          <button
            onClick={() => setQuery("")}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-ink"
          >
            ✕
          </button>
        )}
      </div>

      {query.trim() && (
        <div className="mt-6">
          <p className="text-sm text-gray-500 mb-3">
            {results.length} result{results.length === 1 ? "" : "s"} for “{query}”
          </p>
          {results.length === 0 ? (
            <p className="text-gray-400">No dishes found. Try another keyword.</p>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {results.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}
        </div>
      )}
    </section>
  );
}
