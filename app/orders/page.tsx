import type { Metadata } from "next";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import MyOrders from "@/components/MyOrders";

export const metadata: Metadata = {
  title: "My Orders — Restaurant",
};

export default function OrdersPage() {
  return (
    <>
      <Header />
      <main className="mx-auto max-w-3xl px-4 pt-36 pb-10">
        <h1 className="text-3xl font-extrabold mb-6">My Orders</h1>
        <MyOrders />
      </main>
      <Footer />
    </>
  );
}
