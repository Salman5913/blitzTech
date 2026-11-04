"use client";

/**
 * "We're closed" popup while the website is switched off in the ERP (Restaurant > Website On/Off) or outside its
 * opening hours. It can't be closed: the website can't be used until it opens. The status is checked again every
 * minute, so the popup appears at closing time and goes away at opening time without a reload.
 */

import { useEffect, useState } from "react";
import { apiGet } from "@/lib/fetcher";
import type { WebsiteStatus } from "@/lib/types";

const CHECK_EVERY_MS = 60 * 1000;

export default function ClosedPopup({ initial }: { initial: WebsiteStatus }) {
  const [status, setStatus] = useState(initial);

  useEffect(() => {
    const check = () => apiGet<WebsiteStatus>("/status").then(setStatus).catch(() => {});
    const timer = window.setInterval(check, CHECK_EVERY_MS);
    return () => window.clearInterval(timer);
  }, []);

  // no scrolling the page behind the popup
  useEffect(() => {
    if (status.open) return;
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = overflow;
    };
  }, [status.open]);

  if (status.open) return null;

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="closed-popup-title"
    >
      <div className="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-light text-3xl" aria-hidden="true">
          🕕
        </div>
        <h2 id="closed-popup-title" className="text-2xl font-extrabold text-ink">
          We&apos;re closed
        </h2>
        <p className="mt-2 text-gray-600">{status.message}</p>
        {status.opensAt && (
          <p className="mt-5 inline-block rounded-full bg-primary px-5 py-2 text-sm font-bold text-white">
            Opens at {status.opensAt}
          </p>
        )}
      </div>
    </div>
  );
}
