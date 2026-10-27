"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import {
  getOrders,
  formatPrice,
  ORDER_STAGES,
  stageLabel,
  FINAL_STATUSES,
  COMPANY_PHONE,
  COMPANY_PHONE_DISPLAY,
} from "@/lib/library";
import { apiGet } from "@/lib/fetcher";
import type { ApiOrder, OrderStatus, SavedOrder } from "@/lib/types";
import OrderTracker from "./OrderTracker";

const STATUS_STYLE: Record<OrderStatus, string> = {
  received: "bg-blue-50 text-blue-700",
  preparing: "bg-amber-50 text-amber-700",
  on_the_way: "bg-indigo-50 text-indigo-700",
  completed: "bg-green-50 text-green-700",
  cancelled: "bg-red-50 text-red-700",
};

function statusText(status: OrderStatus, order: SavedOrder): string {
  if (status === "cancelled") return "Cancelled";
  const stage = ORDER_STAGES.find((s) => s.key === status);
  return stage ? stageLabel(stage, order.type) : status;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString("en-PK", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

export default function MyOrders() {
  const [orders, setOrders] = useState<SavedOrder[] | null>(null);
  const [statuses, setStatuses] = useState<Record<string, OrderStatus>>({});
  const [openId, setOpenId] = useState<string | null>(null);

  // Orders placed from this browser; their status comes from the restaurant API
  useEffect(() => {
    const saved = getOrders();
    setOrders(saved);
    saved.forEach((o) => {
      apiGet<ApiOrder>(`/orders/${encodeURIComponent(o.orderId)}`)
        .then((res) => setStatuses((prev) => ({ ...prev, [o.orderId]: res.status })))
        .catch(() => {});
    });
  }, []);

  // Refresh the status list while any order is still in progress
  useEffect(() => {
    if (!orders) return;
    const pending = orders.filter((o) => statuses[o.orderId] && !FINAL_STATUSES.includes(statuses[o.orderId]));
    if (pending.length === 0) return;
    const timer = setInterval(() => {
      pending.forEach((o) => {
        apiGet<ApiOrder>(`/orders/${encodeURIComponent(o.orderId)}`)
          .then((res) => setStatuses((prev) => (prev[o.orderId] === res.status ? prev : { ...prev, [o.orderId]: res.status })))
          .catch(() => {});
      });
    }, 10000);
    return () => clearInterval(timer);
  }, [orders, statuses]);

  if (orders === null) {
    return <p className="text-gray-400 text-center py-10">Loading your orders…</p>;
  }

  if (orders.length === 0) {
    return (
      <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
        <p className="text-5xl mb-4">🧾</p>
        <p className="font-semibold">No orders yet</p>
        <p className="text-sm text-gray-500 mt-1">Orders you place from this device will show up here.</p>
        <Link
          href="/"
          className="inline-block mt-6 bg-primary text-white font-semibold px-6 py-3 rounded-full hover:bg-primary-dark transition"
        >
          Browse the menu
        </Link>
      </div>
    );
  }

  // Unknown status (still loading or API down) counts as current, so the order is not hidden
  const current = orders.filter((o) => !FINAL_STATUSES.includes(statuses[o.orderId]));
  const previous = orders.filter((o) => FINAL_STATUSES.includes(statuses[o.orderId]));

  const renderOrder = (order: SavedOrder) => {
    const status = statuses[order.orderId];
    const isCurrent = !FINAL_STATUSES.includes(status);
    const open = openId === order.orderId;
    return (
      <div key={order.orderId} className="bg-white rounded-2xl shadow-sm p-4">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div>
            <p className="font-bold text-primary">{order.orderId}</p>
            <p className="text-xs text-gray-400">
              {formatDate(order.placedAt)} · {order.type === "delivery" ? "🛵 Delivery" : "🛍️ Pickup"}
              {order.branchName && <> · 📍 {order.branchName}</>}
            </p>
          </div>
          <div className="text-right">
            <span
              className={`inline-block text-xs font-semibold px-2.5 py-1 rounded-full ${
                status ? STATUS_STYLE[status] : "bg-gray-100 text-gray-500"
              }`}
            >
              {status ? statusText(status, order) : "Checking…"}
            </span>
            <p className="font-extrabold mt-1">{formatPrice(order.total)}</p>
          </div>
        </div>

        <ul className="mt-3 text-sm text-gray-600 space-y-0.5">
          {order.items.map((item, i) => (
            <li key={i}>
              {item.qty} × {item.name}
              {item.modifiers.length > 0 && (
                <span className="text-xs text-gray-400"> ({item.modifiers.join(", ")})</span>
              )}
            </li>
          ))}
        </ul>

        {status === "cancelled" && (
          <p className="mt-3 text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">
            This order was cancelled. Please call us for help at{" "}
            <a href={`tel:${COMPANY_PHONE}`} className="font-bold underline whitespace-nowrap">
              📞 {COMPANY_PHONE_DISPLAY}
            </a>
          </p>
        )}

        {isCurrent && (
          <>
            <button
              type="button"
              onClick={() => setOpenId(open ? null : order.orderId)}
              className="mt-3 text-sm font-semibold text-primary hover:underline"
            >
              {open ? "Hide tracking ▲" : "Track order ▼"}
            </button>
            {open && (
              <div className="mt-4 border-t pt-4">
                <OrderTracker orderId={order.orderId} type={order.type} />
              </div>
            )}
          </>
        )}
      </div>
    );
  };

  return (
    <div className="space-y-10">
      <section>
        <h2 className="font-bold text-lg mb-3">Current Orders</h2>
        {current.length === 0 ? (
          <p className="text-sm text-gray-400 bg-white rounded-2xl shadow-sm p-4">No orders in progress.</p>
        ) : (
          <div className="space-y-3">{current.map(renderOrder)}</div>
        )}
      </section>

      <section>
        <h2 className="font-bold text-lg mb-3">Previous Orders</h2>
        {previous.length === 0 ? (
          <p className="text-sm text-gray-400 bg-white rounded-2xl shadow-sm p-4">No previous orders yet.</p>
        ) : (
          <div className="space-y-3">{previous.map(renderOrder)}</div>
        )}
      </section>

      <p className="text-center text-sm text-gray-500">
        Need help with an order? Call{" "}
        <a href={`tel:${COMPANY_PHONE}`} className="font-bold text-primary whitespace-nowrap">
          📞 {COMPANY_PHONE_DISPLAY}
        </a>
      </p>
    </div>
  );
}
