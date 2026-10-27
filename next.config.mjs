/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "images.unsplash.com" },
      // dish photos uploaded in the ERP (served by XAMPP)
      { protocol: "http", hostname: "localhost", pathname: "/demos3/**" },
    ],
  },
};

export default nextConfig;
