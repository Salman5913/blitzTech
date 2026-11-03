import type { Metadata } from "next";
import localFont from "next/font/local";
import "./globals.css";
import ClosedPopup from "@/components/ClosedPopup";
import { backendRequest } from "@/lib/fetcher";
import type { WebsiteStatus } from "@/lib/types";

// Montserrat site-wide, loaded from local files in app/fonts (variable weight 400–800, latin)
const montserrat = localFont({
  src: [
    { path: "./fonts/Montserrat-Variable.woff2", weight: "400 800", style: "normal" },
    { path: "./fonts/Montserrat-Italic-Variable.woff2", weight: "400 800", style: "italic" },
  ],
  display: "swap",
  variable: "--font-montserrat",
});

export const metadata: Metadata = {
  title: "Restaurant — Order Online",
  description: "Fresh food, dynamic menu, order online.",
};

/** Open or closed (ERP: Restaurant > Website On/Off). If the ERP can't be reached the site stays usable. */
async function getWebsiteStatus(): Promise<WebsiteStatus> {
  try {
    return await backendRequest<WebsiteStatus>("/status");
  } catch {
    return { open: true, message: "", opensAt: null };
  }
}

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const status = await getWebsiteStatus();

  return (
    <html lang="en" className={montserrat.variable}>
      <body className={`${montserrat.className} min-h-screen antialiased`}>
        {children}
        <ClosedPopup initial={status} />
      </body>
    </html>
  );
}
