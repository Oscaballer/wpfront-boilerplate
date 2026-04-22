import type { NextConfig } from "next";

// Extrae el hostname de la URL de WordPress para permitir imágenes remotas.
// Ejemplo: "https://cms.webune.com" → "cms.webune.com"
const wpUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL ?? "localhost";
const wpHostname = (() => {
  try {
    return new URL(wpUrl).hostname;
  } catch {
    return wpUrl;
  }
})();

const nextConfig: NextConfig = {
  output: "standalone",

  // Corrige el warning de workspace root cuando hay múltiples lockfiles en el monorepo.
  turbopack: {
    root: __dirname,
  },

  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: wpHostname,
        pathname: "/wp-content/uploads/**",
      },
    ],
  },

  // Seguridad: cabeceras HTTP recomendadas para producción
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "X-Frame-Options", value: "SAMEORIGIN" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
        ],
      },
    ];
  },
};

export default nextConfig;
