"use client";

import { useEffect, useState } from "react";
import { ORDER_STAGES, stageLabel, trackOrder, COMPANY_PHONE, COMPANY_PHONE_DISPLAY } from "@/lib/library";
import type { OrderStatus, OrderType } from "@/lib/types";

export default function OrderTracker({ orderId, type }: { orderId: string; type: OrderType }) {
  const [status, setStatus] = useState<OrderStatus | null>(null);

  useEffect(() => {
    // poll the restaurant API every 5s; updates only when status changes.
    // stop() cancels polling when the component unmounts.
    const stop = trackOrder(orderId, setStatus, 5000);
    return stop;
  }, [orderId]);

  const currentIndex = ORDER_STAGES.findIndex((s) => s.key === status);

  return (
    <div className="max-w-md mx-auto">
      <p className="text-center text-sm text-gray-400 mb-6">
        Order <span className="font-bold text-primary">{orderId}</span> — live
        updates every 5 seconds
      </p>

      {status === "cancelled" ? (
        <p className="text-center font-bold text-red-600 bg-red-50 rounded-xl px-4 py-3">
          This order was cancelled. Please call us for help at{" "}
          <a href={`tel:${COMPANY_PHONE}`} className="underline whitespace-nowrap">
            📞 {COMPANY_PHONE_DISPLAY}
          </a>
        </p>
      ) : (
        <ol className="space-y-0">
          {ORDER_STAGES.map((stage, i) => {
            const reached = status !== null && i <= currentIndex;
            const isLast = i === ORDER_STAGES.length - 1;
            return (
              <li key={stage.key} className="flex gap-4">
                <div className="flex flex-col items-center">
                  <span
                    className={`w-10 h-10 rounded-full flex items-center justify-center text-lg border-2 transition ${
                      reached
                        ? "bg-primary border-primary text-white"
                        : "border-gray-200 bg-white"
                    }`}
                  >
                    {stage.icon}
                  </span>
                  {!isLast && (
                    <span
                      className={`w-0.5 flex-1 min-h-[24px] ${
                        i < currentIndex ? "bg-primary" : "bg-gray-200"
                      }`}
                    />
                  )}
                </div>
                <p
                  className={`pt-2 text-sm font-semibold ${
                    reached ? "text-ink" : "text-gray-300"
                  }`}
                >
                  {stageLabel(stage, type)}
                  {status === stage.key && stage.key !== "completed" && (
                    <span className="ml-2 text-xs font-normal text-primary animate-pulse">
                      ● current
                    </span>
                  )}
                </p>
              </li>
            );
          })}
        </ol>
      )}

      {status === "completed" && (
        <p className="text-center mt-6 font-bold text-green-600">
          🎉 Enjoy your meal! Thank you for ordering.
        </p>
      )}
    </div>
  );
}
