import type { Product } from "@/lib/types";
import ProductCard from "./ProductCard";

export default function PopularItems({ items }: { items: Product[] }) {
  if (!items.length) return null;

  return (
    <section id="popular" className="mx-auto max-w-7xl px-4 mt-12">
      <div className="flex items-end justify-between mb-5">
        <div>
          <h2 className="text-2xl md:text-3xl font-extrabold">Popular Items 🔥</h2>
          <p className="text-gray-500 text-sm mt-1">Most loved by our customers</p>
        </div>
      </div>
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        {items.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
}
