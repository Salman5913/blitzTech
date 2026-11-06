"use client";

import { useRef } from "react";
import type { Category } from "@/lib/types";

export default function CategorySlider({ categories }: { categories: Category[] }) {
  const trackRef = useRef<HTMLDivElement>(null);

  if (!categories.length) return null;

  const scrollTrack = (direction: 1 | -1) => {
    const track = trackRef.current;
    if (track) {
      track.scrollBy({
        left: direction * Math.max(160, track.clientWidth * 0.7),
        behavior: "smooth",
      });
    }
  };

  const arrowClass =
    "absolute top-1/2 -translate-y-1/2 z-10 flex items-center justify-center " +
    "rounded-full bg-primary text-white shadow-md " +
    "hover:bg-white hover:text-primary transition " +
    "w-7 h-7 text-lg " +          // mobile
    "sm:w-8 sm:h-8 sm:text-xl " + // sm+
    "md:w-9 md:h-9";              // desktop (original)

  return (
    <section id="menu" className="sticky top-0 z-40 bg-transparent">
      <div className="relative mx-auto max-w-7xl">
        <div
          ref={trackRef}
          className="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar 
                     py-2 sm:py-3 
                     px-9 sm:px-11 md:px-12"
        >
          {categories.map((cat) => (
            <a
              key={cat.id}
              href={`#category-${cat.slug}`}
              className="flex-shrink-0 whitespace-nowrap rounded-full 
                         px-3 py-1.5 text-xs          /* mobile only */
                         sm:px-4 sm:py-2 sm:text-base /* desktop original */
                         capitalize font-semibold text-white bg-primary 
                         hover:bg-white hover:text-primary transition"
            >
              {cat.name}
            </a>
          ))}
        </div>

        <button
          type="button"
          onClick={() => scrollTrack(-1)}
          aria-label="Scroll categories left"
          className={`${arrowClass} left-1 sm:left-2`}
        >
          ‹
        </button>

        <button
          type="button"
          onClick={() => scrollTrack(1)}
          aria-label="Scroll categories right"
          className={`${arrowClass} right-1 sm:right-2`}
        >
          ›
        </button>
      </div>
    </section>
  );
}