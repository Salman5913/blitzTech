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
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // First visit (or the chosen branch left the website): ask for the branch before anything else
  const branchRequired = branches !== null && branch === null;
  // Derive from hook state (empty on server + first client render) to avoid hydration mismatch
  const count = cart.reduce((sum, item) => sum + item.qty, 0);
  const total = cart.reduce((sum, item) => sum + item.qty * item.unitPrice, 0);

  return (
    <>
    {/* NORMAL FLOW header — scrolls away with the page */}
      <div className="relative z-40 px-3 pt-3">
        <header className="mx-auto max-w-7xl bg-white/95 backdrop-blur rounded-2xl border border-gray-100 shadow-lg shadow-black/5">
          <div className="px-3 sm:px-4 h-16 flex items-center justify-between gap-2">
            {/* Logo */}
            <Link href="/" className="flex items-center gap-1.5 sm:gap-2 min-w-0">
              <span className="text-xl sm:text-2xl">🍽️</span>
              <span className="text-lg sm:text-xl font-extrabold tracking-tight text-primary">
                Restaurant
              </span>
            </Link>

            {/* Desktop nav — unchanged */}
            <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
              <Link href="/" className="hover:text-primary">Home</Link>
              <a href="/#popular" className="hover:text-primary">Popular</a>
              <a href="/#deals" className="hover:text-primary">Deals</a>
              <a href="/#menu" className="hover:text-primary">Menu</a>
              <Link href="/orders" className="hover:text-primary">My Orders</Link>
            </nav>

            <div className="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
              {/* Cart button — always visible */}
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

              {/* Branch selector — desktop only */}
              <button
                onClick={() => setPickerOpen(true)}
                title="Change branch"
                className="hidden md:flex group max-w-[140px] sm:max-w-[200px] items-center gap-1.5 sm:gap-2 rounded-full border border-gray-100 bg-white px-2.5 sm:px-3 py-1.5 text-ink shadow-md shadow-black/10 transition hover:border-gray-200 hover:shadow-lg"
              >
                <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-light text-xs">
                  📍
                </span>
                <span className="flex min-w-0 flex-col items-start leading-tight">
                  <span className="text-[10px] text-gray-400">Branch</span>
                  <span className="w-full truncate text-xs sm:text-sm font-bold">
                    {branch ? branch.name : "Choose"}
                  </span>
                </span>
                <span className="flex-shrink-0 text-[10px] text-gray-400 group-hover:text-primary">
                  ▾
                </span>
              </button>

              {/* Hamburger — mobile only, rightmost */}
              <button
                onClick={() => setMobileMenuOpen(true)}
                className="md:hidden flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-ink hover:bg-gray-50 transition"
                aria-label="Open menu"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
            </div>
          </div>
        </header>

        {/* tabs hanging below the header: "view cart" once the cart has items */}
        <div className="-mt-px flex justify-center gap-2">
          {count > 0 && (
            <button
              onClick={() => setDrawerOpen(true)}
              className="group flex min-w-0 items-center justify-between gap-3 rounded-b-2xl bg-ink px-3 sm:px-4 py-2.5 text-white shadow-lg shadow-black/10 transition hover:bg-primary sm:w-[20%] sm:min-w-[240px]"
            >
              <span className="flex items-center gap-2">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-white/15 text-sm">
                  🛒
                </span>
                <span className="flex flex-col items-start leading-tight">
                  <span className="text-[11px] text-white/70">
                    {count} {count === 1 ? "item" : "items"}
                  </span>
                  <span className="text-sm font-bold whitespace-nowrap">
                    {formatPrice(total)}
                  </span>
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

      {/* Mobile menu drawer */}
      <div
        className={`fixed inset-0 z-50 md:hidden transition-opacity duration-300 ${
          mobileMenuOpen
            ? "opacity-100 pointer-events-auto"
            : "opacity-0 pointer-events-none"
        }`}
      >
        {/* Backdrop */}
        <div
          className="absolute inset-0 bg-black/40 backdrop-blur-sm"
          onClick={() => setMobileMenuOpen(false)}
        />

        {/* Panel */}
        <div
          className={`absolute top-0 right-0 h-full w-[min(320px,85vw)] bg-white shadow-2xl flex flex-col
            transform transition-transform duration-300 ease-out
            ${mobileMenuOpen ? "translate-x-0" : "translate-x-full"}`}
        >
          {/* Header of the drawer */}
          <div className="flex items-center justify-between px-5 h-16 border-b border-gray-100">
            <span className="font-bold text-lg text-primary">Menu</span>
            <button
              onClick={() => setMobileMenuOpen(false)}
              className="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition"
              aria-label="Close menu"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Nav links */}
          <nav className="flex-1 overflow-y-auto py-4 px-3">
            <div className="flex flex-col gap-1">
              <Link
                href="/"
                onClick={() => setMobileMenuOpen(false)}
                className="px-4 py-3 rounded-xl text-base font-medium text-gray-800 hover:bg-primary/10 hover:text-primary transition"
              >
                Home
              </Link>
              <a
                href="/#popular"
                onClick={() => setMobileMenuOpen(false)}
                className="px-4 py-3 rounded-xl text-base font-medium text-gray-800 hover:bg-primary/10 hover:text-primary transition"
              >
                Popular
              </a>
              <a
                href="/#deals"
                onClick={() => setMobileMenuOpen(false)}
                className="px-4 py-3 rounded-xl text-base font-medium text-gray-800 hover:bg-primary/10 hover:text-primary transition"
              >
                Deals
              </a>
              <a
                href="/#menu"
                onClick={() => setMobileMenuOpen(false)}
                className="px-4 py-3 rounded-xl text-base font-medium text-gray-800 hover:bg-primary/10 hover:text-primary transition"
              >
                Menu
              </a>
              <Link
                href="/orders"
                onClick={() => setMobileMenuOpen(false)}
                className="px-4 py-3 rounded-xl text-base font-medium text-gray-800 hover:bg-primary/10 hover:text-primary transition"
              >
                My Orders
              </Link>
            </div>
          </nav>

          {/* Branch picker at the bottom of the menu */}
          <div className="border-t border-gray-100 p-4">
            <button
              onClick={() => {
                setMobileMenuOpen(false);
                setPickerOpen(true);
              }}
              className="w-full flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-left hover:border-primary/40 hover:bg-primary/5 transition"
            >
              <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-primary-light text-base">
                📍
              </span>
              <span className="flex min-w-0 flex-col">
                <span className="text-xs text-gray-400">Branch</span>
                <span className="font-bold text-sm truncate">
                  {branch ? branch.name : "Choose branch"}
                </span>
              </span>
              <span className="ml-auto text-gray-400">▾</span>
            </button>
          </div>
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