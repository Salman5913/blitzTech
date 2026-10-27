import type { Product } from "@/lib/types";
import ProductCard from "./ProductCard";

export default function DealsSection({ deals }: { deals: Product[] }) {
  if (!deals.length) return null;

  return (
    <section id="deals" className="mt-12 bg-primary-light/60 py-10">
      <div className="mx-auto max-w-7xl px-4">
        <div className="mb-5">
          <h2 className="text-2xl md:text-3xl font-extrabold text-primary-dark">
            Hot Deals 🎉
          </h2>
          <p className="text-gray-500 text-sm mt-1">Limited-time combos and savings</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
          {deals.map((deal) => (
            <ProductCard key={deal.id} product={deal} />
          ))}
        </div>
      </div>
    </section>
  );
}
