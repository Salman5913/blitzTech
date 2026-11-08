"use client";

import { useRef, useState, useEffect } from "react";
import type { Category } from "@/lib/types";

export default function CategorySlider({ categories }: { categories: Category[] }) {
  const trackRef = useRef<HTMLDivElement>(null);
  const [activeSlug, setActiveSlug] = useState<string | null>(null);

  // ✅ All hooks must be called unconditionally
  useEffect(() => {
    if (!categories.length) return; // safe inside the effect

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

        if (visible.length > 0) {
          const id = visible[0].target.id;
          setActiveSlug(id.replace(/^category-/, ""));
          return;
        }

        // Nothing intersecting → check if we are above the first category
        const firstSection = document.getElementById(
          `category-${categories[0].slug}`
        );

        if (firstSection) {
          const rect = firstSection.getBoundingClientRect();
          if (rect.top > 100) {
            setActiveSlug(null);
          }
        }
      },
      {
        rootMargin: "-100px 0px -55% 0px",
        threshold: 0.15,
      }
    );

    categories.forEach((cat) => {
      const el = document.getElementById(`category-${cat.slug}`);
      if (el) observer.observe(el);
    });

    return () => observer.disconnect();
  }, [categories]);

  // ✅ Early return only after all hooks
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
    "w-7 h-7 text-lg " +
    "sm:w-8 sm:h-8 sm:text-xl " +
    "md:w-9 md:h-9";

  return (
    <section id="menu" className="sticky top-0 z-40 bg-transparent">
      <div className="relative mx-auto max-w-7xl">
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

        <div className="mx-9 sm:mx-11 md:mx-12 overflow-hidden">
          <div
            ref={trackRef}
            className="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar 
                       py-2 sm:py-3"
          >
            {categories.map((cat) => {
              const isActive = activeSlug === cat.slug;

              return (
                <a
                  key={cat.id}
                  href={`#category-${cat.slug}`}
                  className={`
                    flex-shrink-0 whitespace-nowrap rounded-full 
                    px-3 py-1.5 text-xs
                    sm:px-4 sm:py-2 sm:text-base
                    capitalize font-semibold transition
                    ${
                      isActive
                        ? "bg-white text-primary"
                        : "bg-primary text-white hover:bg-white hover:text-primary"
                    }
                  `}
                >
                  {cat.name}
                </a>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}