"use client";

import { useRef } from "react";
import type { Category } from "@/lib/types";

export default function CategorySlider({ categories }: { categories: Category[] }) {
  const trackRef = useRef<HTMLDivElement>(null);

  if (!categories.length) return null;

  // Arrows move the row sideways (a mouse wheel only scrolls the page, and the scrollbar is hidden)
  const scrollTrack = (direction: 1 | -1) => {
    // Most of the visible width per click, so it fits small phone screens too
    const track = trackRef.current;
    if (track) track.scrollBy({ left: direction * Math.max(160, track.clientWidth * 0.7), behavior: "smooth" });
  };

  const arrowClass =
    "flex flex-shrink-0 w-8 h-8 md:w-9 md:h-9 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl leading-none items-center justify-center transition";

  return (
    <section id="menu" className="bg-primary">
      <div className="mx-auto max-w-7xl flex items-center gap-2 px-2">
        <button type="button" onClick={() => scrollTrack(-1)} aria-label="Scroll categories left" className={arrowClass}>
          ‹
        </button>

        <div
          ref={trackRef}
          className="flex flex-1 min-w-0 items-center gap-1 overflow-x-auto no-scrollbar py-3"
        >
          {categories.map((cat) => (
            <a
              key={cat.id}
              href={`#category-${cat.slug}`}
              className="flex-shrink-0 whitespace-nowrap rounded-full px-4 py-2 capitalize font-semibold text-white hover:bg-white hover:text-primary transition"
            >
              {cat.name}
            </a>
          ))}
        </div>

        <button type="button" onClick={() => scrollTrack(1)} aria-label="Scroll categories right" className={arrowClass}>
          ›
        </button>
      </div>
    </section>
  );
}
