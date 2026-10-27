"use client";

import { useRouter } from "next/navigation";
import { selectBranch } from "@/lib/branch";
import type { ApiBranch } from "@/lib/types";

interface Props {
  branches: ApiBranch[];
  current: ApiBranch | null;
  // No branch chosen yet: the picker can't be closed until one is picked
  required: boolean;
  onClose: () => void;
}

export default function BranchPicker({ branches, current, required, onClose }: Props) {
  const router = useRouter();

  const choose = (branch: ApiBranch) => {
    if (branch.id !== current?.id && !selectBranch(branch.id)) return;
    onClose();
    // Server Components (the home page menu) re-render with the new branch cookie
    router.refresh();
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50" onClick={required ? undefined : onClose} />
      <div className="relative z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-6">
        <div className="flex items-start justify-between gap-4 mb-1">
          <h2 className="text-xl font-extrabold">📍 Choose your branch</h2>
          {!required && (
            <button
              onClick={onClose}
              aria-label="Close"
              className="w-9 h-9 -mt-1 -mr-2 rounded-full flex items-center justify-center text-gray-400 hover:text-ink hover:bg-gray-100 text-lg transition"
            >
              ✕
            </button>
          )}
        </div>
        <p className="text-sm text-gray-500 mb-5">Menu, deals and delivery areas depend on the branch you order from.</p>

        {branches.length === 0 ? (
          <p className="text-sm text-gray-400 text-center py-6">No branches are taking online orders right now.</p>
        ) : (
          <div className="space-y-2 max-h-[60vh] overflow-y-auto">
            {branches.map((branch) => {
              const selected = branch.id === current?.id;
              return (
                <button
                  key={branch.id}
                  type="button"
                  onClick={() => choose(branch)}
                  className={`w-full text-left border rounded-2xl px-4 py-3 transition ${
                    selected ? "border-primary bg-primary-light/50" : "border-gray-200 hover:border-primary"
                  }`}
                >
                  <span className="flex items-center justify-between gap-3">
                    <span className="font-bold">{branch.name}</span>
                    {selected && <span className="text-xs font-semibold text-primary">✓ Selected</span>}
                  </span>
                  {branch.address && <span className="block text-sm text-gray-500 mt-0.5">{branch.address}</span>}
                  {branch.phone && <span className="block text-xs text-gray-400 mt-0.5">📞 {branch.phone}</span>}
                </button>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
