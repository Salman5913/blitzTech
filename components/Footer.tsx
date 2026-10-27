export default function Footer() {
  return (
    <footer className="bg-ink text-white mt-16">
      <div className="mx-auto max-w-7xl px-4 py-12 grid gap-8 md:grid-cols-3">
        <div>
          <p className="text-xl font-extrabold text-white">🍽️ Restaurant</p>
          <p className="text-sm text-gray-400 mt-2 max-w-xs">
            Fresh, fast and flavorful. Order your favorite dishes online and get
            them delivered hot.
          </p>
        </div>
        <div>
          <p className="font-semibold mb-3">Quick Links</p>
          <ul className="text-sm text-gray-400 space-y-2">
            <li><a href="/#popular" className="hover:text-white">Popular Items</a></li>
            <li><a href="/#deals" className="hover:text-white">Deals</a></li>
            <li><a href="/#menu" className="hover:text-white">Menu</a></li>
          </ul>
        </div>
        <div>
          <p className="font-semibold mb-3">Contact</p>
          <ul className="text-sm text-gray-400 space-y-2">
            <li><a href="tel:+923131186728" className="hover:text-white">📞 +92 313 1186728</a></li>
            <li>📧 order@restaurant.com</li>
            <li>📍 Main Boulevard, Karachi</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-white/10 py-4 text-center text-xs text-gray-500">
        © {new Date().getFullYear()} Restaurant. All rights reserved.
      </div>
    </footer>
  );
}
