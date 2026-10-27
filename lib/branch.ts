"use client";

/**
 * The branch the customer orders from. Every branch has its own menu, deals and delivery areas, so the
 * choice is kept in a cookie: the home page (a Server Component) reads it to load that branch's menu.
 */

import { useEffect, useState } from "react";
import { apiGet } from "./fetcher";
import { clearCart, getCart } from "./library";
import { BRANCH_COOKIE, type ApiBranch } from "./types";

const BRANCH_EVENT = "branch-updated";

export function getSelectedBranchId(): number | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp(`(?:^|; )${BRANCH_COOKIE}=(\\d+)`));
  return match ? Number(match[1]) : null;
}

/**
 * Switches to another branch. The cart holds that branch's dishes, so it is emptied on a change.
 * Returns false when the customer keeps the current branch (they did not want to lose a full cart).
 */
export function selectBranch(branchId: number): boolean {
  const current = getSelectedBranchId();
  if (current !== null && current !== branchId && getCart().length > 0) {
    if (!window.confirm("Your cart has items from the other branch. Switching branch will empty your cart. Continue?")) {
      return false;
    }
    clearCart();
  }
  document.cookie = `${BRANCH_COOKIE}=${branchId}; path=/; max-age=${60 * 60 * 24 * 365}; samesite=lax`;
  window.dispatchEvent(new Event(BRANCH_EVENT));
  return true;
}

/** Branches from the restaurant API (null while loading) and the chosen one; updates when the branch changes */
export function useBranch(): { branches: ApiBranch[] | null; branch: ApiBranch | null } {
  const [branches, setBranches] = useState<ApiBranch[] | null>(null);
  const [branchId, setBranchId] = useState<number | null>(null);

  useEffect(() => {
    apiGet<ApiBranch[]>("/branches")
      .then(setBranches)
      .catch(() => setBranches([]));
    const refresh = () => setBranchId(getSelectedBranchId());
    refresh();
    window.addEventListener(BRANCH_EVENT, refresh);
    return () => window.removeEventListener(BRANCH_EVENT, refresh);
  }, []);

  // A cookie for a branch that is no longer on the website counts as no branch
  const branch = branches?.find((b) => b.id === branchId) ?? null;
  return { branches, branch };
}
