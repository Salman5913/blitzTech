"use client";

import Link from "next/link";
import { useCart, formatPrice } from "@/lib/library";
import { useBranch } from "@/lib/branch";
import CartDrawer from "./CartDrawer";
import BranchPicker from "./BranchPicker";
import { useState } from "react";

export default function Header() {
  const cart = useCart();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const { branches, branch } = useBranch();
  const [pickerOpen, setPickerOpen] = useState(false);
  // First visit (or the chosen branch left the website): ask for the branch before anything else
  const branchRequired = branches !== null && branch === null;
  // Derive from hook state (empty on server + first client render) to avoid hydration mismatch
  const count = cart.reduce((sum, item) => sum + item.qty, 0);
  const total = cart.reduce((sum, item) => sum + item.qty * item.unitPrice, 0);

  return (
    <>
      {/* FIXED floating header — overlays content, takes no space in layout */}
      <div className="fixed top-0 left-0 right-0 z-40 px-3 pt-3">
        <header className="mx-auto max-w-7xl bg-white/95 backdrop-blur rounded-2xl border border-gray-100 shadow-lg shadow-black/5">
          <div className="px-3 sm:px-4 h-16 flex items-center justify-between gap-2">
            <Link href="/" className="flex items-center gap-1.5 sm:gap-2 min-w-0">
              <span className="text-xl sm:text-2xl">🍽️</span>
              <span className="text-lg sm:text-xl font-extrabold tracking-tight text-primary">
                Restaurant
              </span>
            </Link>

            <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
              <Link href="/" className="hover:text-primary">Home</Link>
              <a href="/#popular" className="hover:text-primary">Popular</a>
              <a href="/#deals" className="hover:text-primary">Deals</a>
              <a href="/#menu" className="hover:text-primary">Menu</a>
              <Link href="/orders" className="hover:text-primary">My Orders</Link>
            </nav>

            <div className="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
              {/* nav is hidden on small screens; keep My Orders reachable there */}
              <Link
                href="/orders"
                className="md:hidden whitespace-nowrap text-xs font-semibold text-gray-600 hover:text-primary px-2.5 py-2 rounded-full border border-gray-200"
              >
                📋 My Orders
              </Link>
              <button
                onClick={() => setDrawerOpen(true)}
                className="relative flex items-center gap-1.5 sm:gap-2 whitespace-nowrap bg-primary text-white text-xs sm:text-sm font-semibold px-3 sm:px-4 py-2 rounded-full hover:bg-primary-dark transition"
              >
                🛒 Cart
                {count > 0 && (
                  <span className="absolute -top-1.5 -right-1.5 bg-ink text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">
                    {count}
                  </span>
                )}
              </button>
            </div>
          </div>
        </header>

        {/* tabs hanging below the header: the branch, and "view cart" once the cart has items */}
        <div className="-mt-px flex justify-center gap-2">
          <button
            onClick={() => setPickerOpen(true)}
            title="Change branch"
            className="group flex min-w-0 max-w-[45%] sm:max-w-[260px] items-center gap-2 rounded-b-2xl border border-t-0 border-gray-100 bg-white px-3 sm:px-4 py-2.5 text-ink shadow-lg shadow-black/10 transition hover:border-primary"
          >
            <span className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-light text-sm">📍</span>
            <span className="flex min-w-0 flex-col items-start leading-tight">
              <span className="text-[11px] text-gray-400">Branch</span>
              <span className="w-full truncate text-sm font-bold">{branch ? branch.name : "Choose"}</span>
            </span>
            <span className="flex-shrink-0 text-xs text-gray-400 group-hover:text-primary">▾</span>
          </button>

          {count > 0 && (
            <button
              onClick={() => setDrawerOpen(true)}
              className="group flex min-w-0 items-center justify-between gap-3 rounded-b-2xl bg-ink px-3 sm:px-4 py-2.5 text-white shadow-lg shadow-black/10 transition hover:bg-primary sm:w-[20%] sm:min-w-[240px]"
            >
              <span className="flex items-center gap-2">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-white/15 text-sm">🛒</span>
                <span className="flex flex-col items-start leading-tight">
                  <span className="text-[11px] text-white/70">
                    {count} {count === 1 ? "item" : "items"}
                  </span>
                  <span className="text-sm font-bold whitespace-nowrap">{formatPrice(total)}</span>
                </span>
              </span>
              <span className="flex items-center gap-1 text-xs font-semibold whitespace-nowrap">
                <span className="hidden sm:inline">View cart</span>
                <span className="transition-transform group-hover:translate-x-0.5">→</span>
              </span>
            </button>
          )}
        </div>
      </div>

      <CartDrawer open={drawerOpen} onClose={() => setDrawerOpen(false)} items={cart} />

      {(pickerOpen || branchRequired) && branches !== null && (
        <BranchPicker
          branches={branches}
          current={branch}
          required={branchRequired}
          onClose={() => setPickerOpen(false)}
        />
      )}
    </>
  );
}