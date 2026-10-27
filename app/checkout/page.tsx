import type { Metadata } from "next";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import CheckoutForm from "@/components/CheckoutForm";

export const metadata: Metadata = {
  title: "Checkout — Restaurant",
};

export default function CheckoutPage() {
  return (
    <>
      <Header />
      <main className="mx-auto max-w-3xl px-4 pt-36 pb-10">
        <h1 className="text-3xl font-extrabold mb-6">Checkout</h1>
        <CheckoutForm />
      </main>
      <Footer />
    </>
  );
}
