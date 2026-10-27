"use client";

import Image from "next/image";
import type { HeroSlide } from "@/lib/types";
import { useEffect, useState } from "react";

export default function HeroSlider({ slides }: { slides: HeroSlide[] }) {
  const [index, setIndex] = useState(0);
  const [touchX, setTouchX] = useState<number | null>(null);

  const goNext = () => setIndex((i) => (i + 1) % slides.length);
  const goPrev = () => setIndex((i) => (i - 1 + slides.length) % slides.length);

  // auto-advance every 5 seconds (timer resets when user clicks arrows/dots)
  useEffect(() => {
    if (slides.length <= 1) return;
    const t = setInterval(goNext, 5000);
    return () => clearInterval(t);
  }, [slides.length, index]);

  if (!slides.length) return null;

  return (
    <section
      className="relative h-[420px] md:h-[520px] overflow-hidden"
      // swipe left / right on phones (the arrows are hidden there so they don't cover the text)
      onTouchStart={(e) => setTouchX(e.touches[0].clientX)}
      onTouchEnd={(e) => {
        if (touchX === null || slides.length <= 1) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (dx > 50) goPrev();
        else if (dx < -50) goNext();
        setTouchX(null);
      }}
    >
      {slides.map((s, i) => (
        <div
          key={s.id}
          className={`absolute inset-0 transition-opacity duration-700 ${
            i === index ? "opacity-100" : "opacity-0 pointer-events-none"
          }`}
        >
          <Image
            src={s.image}
            alt={s.title}
            fill
            priority={i === 0}
            className="object-cover"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent" />
          <div className="relative z-10 mx-auto max-w-7xl h-full px-4 flex flex-col justify-center">
            <h1 className="text-white text-4xl md:text-6xl font-extrabold max-w-xl leading-tight">
              {s.title}
            </h1>
            <p className="text-white/85 mt-4 max-w-md text-base md:text-lg">
              {s.subtitle}
            </p>
            <a
              href="#menu"
              className="mt-6 inline-flex w-fit bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3 rounded-full transition"
            >
              {s.ctaText}
            </a>
          </div>
        </div>
      ))}

      {/* left arrow */}
      {slides.length > 1 && (
        <button
          onClick={goPrev}
          aria-label="Previous slide"
          className="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur text-white text-xl hidden md:flex items-center justify-center transition"
        >
          ‹
        </button>
      )}

      {/* right arrow */}
      {slides.length > 1 && (
        <button
          onClick={goNext}
          aria-label="Next slide"
          className="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur text-white text-xl hidden md:flex items-center justify-center transition"
        >
          ›
        </button>
      )}

      {/* dots */}
      <div className="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex gap-2">
        {slides.map((s, i) => (
          <button
            key={s.id}
            onClick={() => setIndex(i)}
            aria-label={`Slide ${i + 1}`}
            className={`h-2.5 rounded-full transition-all ${
              i === index ? "w-8 bg-white" : "w-2.5 bg-white/50"
            }`}
          />
        ))}
      </div>
    </section>
  );
}
