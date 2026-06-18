/**
 * lib/graphql/queries/posts.ts
 *
 * Queries y funciones de fetching para Posts de WordPress vía WPGraphQL.
 */
import { graphqlClient, type GraphQLRequestOptions } from "../client";
import type {
  WpPost,
  WpPostsResponse,
  WpPostResponse,
} from "../../types/wordpress";

/** Tagged template literal para sintaxis GraphQL — retorna la cadena tal cual. */
const gql = (strings: TemplateStringsArray, ...values: unknown[]) =>
  strings.reduce((acc, s, i) => acc + s + (values[i] ?? ""), "");

// ─── Fragmentos reutilizables ─────────────────────────────────────────────────

export const POST_CARD_FIELDS = gql`
  fragment PostCardFields on Post {
    id
    title
    slug
    date
    excerpt
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
    categories {
      nodes {
        name
        slug
      }
    }
    author {
      node {
        name
        avatar {
          url
        }
      }
    }
  }
`;

// ─── Queries ──────────────────────────────────────────────────────────────────

export const GET_POSTS = gql`
  ${POST_CARD_FIELDS}
  query GetPosts($first: Int, $after: String, $last: Int, $before: String) {
    posts(
      first: $first
      after: $after
      last: $last
      before: $before
      where: { status: PUBLISH }
    ) {
      nodes {
        ...PostCardFields
      }
      pageInfo {
        hasNextPage
        hasPreviousPage
        startCursor
        endCursor
      }
    }
  }
`;

export const GET_POST_BY_SLUG = gql`
  query GetPostBySlug($slug: ID!) {
    post(id: $slug, idType: SLUG) {
      id
      title
      slug
      date
      modified
      content
      excerpt
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
      categories {
        nodes {
          name
          slug
        }
      }
      tags {
        nodes {
          name
          slug
        }
      }
      author {
        node {
          name
          slug
          avatar {
            url
          }
        }
      }
    }
  }
`;

export const GET_ALL_POST_SLUGS = gql`
  query GetAllPostSlugs {
    posts(first: 1000, where: { status: PUBLISH }) {
      nodes {
        slug
      }
    }
  }
`;

// ─── Funciones de fetching ────────────────────────────────────────────────────

/**
 * Obtiene una lista paginada de posts publicados.
 */
export async function getPosts(
  first = 12,
  after?: string,
  language?: string,
  options?: GraphQLRequestOptions
): Promise<WpPostsResponse["posts"]> {
  const data = await graphqlClient.request<WpPostsResponse>(
    GET_POSTS,
    { first, after },
    options
  );
  return data.posts;
}

/**
 * Obtiene un post por su slug.
 * Retorna null si el post no existe.
 */
export async function getPostBySlug(
  slug: string,
  language?: string,
  options?: GraphQLRequestOptions
): Promise<WpPost | null> {
  const data = await graphqlClient.request<WpPostResponse>(
    GET_POST_BY_SLUG,
    { slug },
    options
  );
  return data.post;
}

/**
 * Obtiene todos los slugs de posts publicados.
 * Útil para generateStaticParams() en rutas dinámicas.
 */
export async function getAllPostSlugs(
  options?: GraphQLRequestOptions
): Promise<{ slug: string }[]> {
  const data = await graphqlClient.request<{
    posts: { nodes: { slug: string }[] };
  }>(GET_ALL_POST_SLUGS, undefined, options);
  return data.posts.nodes;
}
