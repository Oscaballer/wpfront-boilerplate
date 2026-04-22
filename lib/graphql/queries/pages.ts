/**
 * lib/graphql/queries/pages.ts
 *
 * Queries y funciones de fetching para Páginas de WordPress vía WPGraphQL.
 */
import { gql } from "graphql-request";
import { graphqlClient } from "../client";
import type {
  WpPage,
  WpPageResponse,
  WpPagesResponse,
} from "../../types/wordpress";

// ─── Queries ──────────────────────────────────────────────────────────────────

const GET_PAGE_BY_SLUG = gql`
  query GetPageBySlug($slug: ID!) {
    page(id: $slug, idType: URI) {
      id
      title
      slug
      content
      featuredImage {
        node {
          sourceUrl
          altText
          mediaDetails {
            width
            height
          }
        }
      }
    }
  }
`;

const GET_ALL_PAGES = gql`
  query GetAllPages {
    pages(first: 100, where: { status: PUBLISH }) {
      nodes {
        id
        title
        slug
      }
    }
  }
`;

// ─── Funciones de fetching ────────────────────────────────────────────────────

/**
 * Obtiene una página WordPress por su slug/URI.
 * Retorna null si la página no existe.
 */
export async function getPageBySlug(slug: string): Promise<WpPage | null> {
  const data = await graphqlClient.request<WpPageResponse>(GET_PAGE_BY_SLUG, {
    slug,
  });
  return data.page;
}

/**
 * Obtiene todas las páginas publicadas.
 * Útil para generar rutas estáticas.
 */
export async function getAllPages(): Promise<WpPagesResponse["pages"]["nodes"]> {
  const data = await graphqlClient.request<WpPagesResponse>(GET_ALL_PAGES);
  return data.pages.nodes;
}
