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
