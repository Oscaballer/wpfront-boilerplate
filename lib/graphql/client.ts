/**
 * lib/graphql/client.ts
 *
 * Cliente GraphQL singleton para WPGraphQL.
 * Usa la variable de entorno NEXT_PUBLIC_WORDPRESS_URL configurada
 * en .env.local (development) o como build ARG en Docker (producción).
 */
import { GraphQLClient } from "graphql-request";

const endpoint = `${
  process.env.NEXT_PUBLIC_WORDPRESS_URL ?? "http://localhost:8080"
}/graphql`;

/**
 * Cliente GraphQL reutilizable en toda la aplicación.
 * En Next.js App Router, este módulo se evalúa en el servidor
 * (Server Components y Route Handlers), por lo que es seguro aquí.
 */
export const graphqlClient = new GraphQLClient(endpoint, {
  headers: {
    "Content-Type": "application/json",
  },
  // En producción puedes agregar autenticación aquí si necesitas
  // acceder a contenido privado de WordPress:
  // headers: { Authorization: `Bearer ${process.env.WP_AUTH_TOKEN}` },
});
