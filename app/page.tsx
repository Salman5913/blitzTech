import { cookies } from "next/headers";
import { getHomePageData, getBranches, flattenProducts } from "@/lib/data";
import { BRANCH_COOKIE } from "@/lib/types";
import Header from "@/components/Header";
import HeroSlider from "@/components/HeroSlider";
import CategorySlider from "@/components/CategorySlider";
import SearchBar from "@/components/SearchBar";
import PopularItems from "@/components/PopularItems";
import DealsSection from "@/components/DealsSection";
import CategorySection from "@/components/CategorySection";
import Footer from "@/components/Footer";

export default async function Home() {
  // The menu of the branch the customer picked (cookie set by the branch picker in the header).
  // A branch that is no longer on the website counts as none; the header then asks again.
  const cookieBranch = Number(cookies().get(BRANCH_COOKIE)?.value) || null;
  const branches = cookieBranch === null ? [] : await getBranches();
  const branchId = branches.some((b) => b.id === cookieBranch) ? cookieBranch : null;

  const data = await getHomePageData(branchId);
  const allProducts = flattenProducts(data);

  return (
    <>
      <Header />
      {/* without a hero the content starts below the fixed header and its hanging tabs */}
      <main className={data.heroSlides.length ? "pb-16" : "pt-36 pb-16"}>
        {/* SECTION 1 — dynamic hero slider */}
        <HeroSlider slides={data.heroSlides} />

        {/* Category slider (after first section) */}
        <CategorySlider categories={data.categories} />

        {/* Search bar (after category slider) */}
        <SearchBar products={allProducts} />

        {/* SECTION 2 — popular items (dynamic) */}
        <PopularItems items={data.popularItems} />

        {/* SECTION 3 — deals (dynamic) */}
        <DealsSection deals={data.deals} />

        {/* ALL REMAINING SECTIONS — dynamic categories + their products */}
        {data.categories.map((category) => (
          <CategorySection key={category.id} category={category} />
        ))}
      </main>
      <Footer />
    </>
  );
}
