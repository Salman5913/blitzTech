import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./app/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["var(--font-montserrat)", "ui-sans-serif", "system-ui", "sans-serif"],
      },
      colors: {
        primary: {
          DEFAULT: "#0b3866",
          dark: "#0b3866",
          light: "#FBE9EA",
        },
        ink: "#1F2430",
        cream: "#FFF8F0",
      },
    },
  },
  plugins: [],
};

export default config;
