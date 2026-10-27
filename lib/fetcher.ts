/**
 * Central fetcher for all API calls.
 *
 * Two paths:
 * - backendRequest(): server side only (Server Components, the /api proxy). Calls the restaurant
 *   REST API (demos3/restaurant_bridge/api/index.php?route=/...) and adds the API key, which never reaches the browser.
 * - apiGet / apiPost / ...: browser side. Calls the same-origin /api proxy (app/api/[...path]/route.ts).
 */

const BACKEND_URL = process.env.RESTAURANT_API_URL || "http://localhost/demos3/restaurant_bridge/api/index.php";
const BACKEND_KEY = process.env.RESTAURANT_API_KEY || "";
const BROWSER_BASE = "/api";

export class BackendError extends Error {
  constructor(public status: number, message: string) {
    super(message);
  }
}

export async function backendRequest<T>(route: string, init: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: "application/json" };
  if (init.body) headers["Content-Type"] = "application/json";
  if (BACKEND_KEY) headers["X-API-Key"] = BACKEND_KEY;
  console.log('BACKEND_URL',BACKEND_URL,route)

  const res = await fetch(`${BACKEND_URL}?route=${encodeURIComponent(route)}`, {
    ...init,
    headers: { ...headers, ...(init.headers as Record<string, string> | undefined) },
    cache: "no-store",
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new BackendError(res.status, data.error || `Request failed (${res.status})`);
  return data as T;
}

/* ---------- browser side: same-origin proxy ---------- */

async function browserRequest<T>(method: string, endpoint: string, body?: unknown): Promise<T> {
  const res = await fetch(`${BROWSER_BASE}${endpoint}`, {
    method,
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
    cache: "no-store",
  });
  const data = await res.json().catch(() => ({}));
  // Keep the server's message (e.g. "Please choose Extras") so the UI can show it
  if (!res.ok) throw new Error(data.error || `${method} ${endpoint} failed: ${res.status}`);
  return data as T;
}

export function apiGet<T>(endpoint: string): Promise<T> {
  return browserRequest<T>("GET", endpoint);
}

export function apiPost<T>(endpoint: string, body: unknown): Promise<T> {
  return browserRequest<T>("POST", endpoint, body);
}
