"use client";

/**
 * app/providers.tsx
 *
 * Árbol de Providers del cliente. Se envuelve en el RootLayout para que
 * todos los Client Components de la app tengan acceso a:
 *   - TanStack Query (useQuery, useMutation, etc.)
 *   - ReactQueryDevtools en desarrollo
 *
 * Al estar marcado con "use client", el RootLayout puede seguir siendo
 * un Server Component.
 */
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import { useState, type ReactNode } from "react";
import ScrollAnimations from "@/components/ScrollAnimations/ScrollAnimations";

interface ProvidersProps {
  children: ReactNode;
}

export default function Providers({ children }: ProvidersProps) {
  /**
   * Se crea la instancia con useState para que cada request en el servidor
   * tenga su propio QueryClient, evitando compartir estado entre usuarios.
   */
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            // Tiempo que los datos se consideran "fresh" antes de re-fetch.
            // 1 minuto es un buen punto de partida para CMS.
            staleTime: 60 * 1000,
            // En error, reintenta 1 vez en lugar del default de 3.
            retry: 1,
          },
        },
      })
  );

  return (
    <QueryClientProvider client={queryClient}>
      <ScrollAnimations />
      {children}
      {/* Las DevTools solo se cargan en desarrollo gracias a tree-shaking */}
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  );
}
