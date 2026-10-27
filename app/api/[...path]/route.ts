import { NextRequest, NextResponse } from "next/server";
import { backendRequest, BackendError } from "@/lib/fetcher";

/**
 * Same-origin proxy to the restaurant API. The browser calls /api/branches, /api/branches/1/areas, /api/orders, ...;
 * this forwards them to demos3/restaurant_bridge/api/index.php and adds the API key on the server.
 * Only the routes the website uses are allowed.
 */
const ALLOWED = new Set(["branches", "orders", "health"]);
const METHODS = new Set(["GET", "POST"]);

export const dynamic = "force-dynamic";

async function forward(req: NextRequest, path: string[]) {
  const [first] = path;
  if (!ALLOWED.has(first) || !METHODS.has(req.method)) {
    return NextResponse.json({ error: "Not found" }, { status: 404 });
  }

  const route = "/" + path.join("/");
  const body = req.method === "GET" ? undefined : await req.text();

  try {
    const data = await backendRequest(route, { method: req.method, body });
    return NextResponse.json(data);
  } catch (e) {
    if (e instanceof BackendError) {
      return NextResponse.json({ error: e.message }, { status: e.status });
    }
    return NextResponse.json({ error: "Restaurant service unavailable" }, { status: 502 });
  }
}

export function GET(req: NextRequest, { params }: { params: { path: string[] } }) {
  return forward(req, params.path);
}

export function POST(req: NextRequest, { params }: { params: { path: string[] } }) {
  return forward(req, params.path);
}
