import type { NextConfig } from "next";

// Extrae el hostname de la URL de WordPress para permitir imágenes remotas.
// Ejemplo: "https://cms.webune.com" → "cms.webune.com"
const wpUrl = process.env.WORDPRESS_URL ?? process.env.NEXT_PUBLIC_WORDPRESS_URL ?? "localhost";
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
    unoptimized: true,
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'media.derechoune.edu.py',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '3000',
      },
      {
        protocol: 'http',
        hostname: '0.0.0.0',
        port: '3000',
      },
    ],
    dangerouslyAllowLocalIP: true,
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
          { key: "X-XSS-Protection", value: "1; mode=block" },
          { key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains; preload" },
          { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=(), interest-cohort=()" },
        ],
      },
    ];
  },
};

export default nextConfig;
