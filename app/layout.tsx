import type { Metadata } from "next";
import localFont from "next/font/local";
import "./globals.css";

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

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={montserrat.variable}>
      <body className={`${montserrat.className} min-h-screen antialiased`}>{children}</body>
    </html>
  );
}
