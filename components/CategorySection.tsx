import type { Category } from "@/lib/types";
import ProductCard from "./ProductCard";

export default function CategorySection({ category }: { category: Category }) {
  if (!category.products.length) return null;

  return (
    <section
      id={`category-${category.slug}`}
      className="mx-auto max-w-7xl px-4 mt-12 scroll-mt-24"
    >
      <div className="mb-5">
        <h2 className="text-2xl md:text-3xl font-extrabold">{category.name}</h2>
        <p className="text-gray-500 text-sm mt-1">
          {category.products.length} items
        </p>
      </div>
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        {category.products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
}
