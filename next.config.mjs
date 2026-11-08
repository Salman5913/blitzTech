<<<<<<< Updated upstream
/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "images.unsplash.com" },
      // dish photos uploaded in the ERP (served by XAMPP)
      { protocol: "https", hostname: "erp.blitzztech.com", pathname: "/**" },
    ],
  },
};

export default nextConfig;
=======
/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "images.unsplash.com" },
      // dish photos uploaded in the ERP (live server)
      { protocol: "https", hostname: "erp.blitzztech.com", pathname: "/assets/**" },
      // dish photos uploaded in the ERP (local XAMPP)
      { protocol: "http", hostname: "localhost", pathname: "/blitzztech/**" },
    ],
  },
};

export default nextConfig;
>>>>>>> Stashed changes
