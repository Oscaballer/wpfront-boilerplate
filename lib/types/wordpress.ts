/**
 * lib/types/wordpress.ts
 *
 * Tipos TypeScript que mapean las respuestas de WPGraphQL.
 * Ajusta los campos según las queries que uses en tu proyecto.
 */

// ─── Imagen ──────────────────────────────────────────────────────────────────

export interface WpMediaItem {
  sourceUrl: string;
  altText: string;
  mediaDetails?: {
    width: number;
    height: number;
  };
}

// ─── Categoría ───────────────────────────────────────────────────────────────

export interface WpCategory {
  name: string;
  slug: string;
}

// ─── Autor ───────────────────────────────────────────────────────────────────

export interface WpAuthor {
  name: string;
  slug: string;
  avatar?: {
    url: string;
  };
}

// ─── Post ────────────────────────────────────────────────────────────────────

export interface WpPost {
  id: string;
  title: string;
  slug: string;
  date: string;           // ISO 8601
  modified: string;       // ISO 8601
  excerpt: string;        // HTML
  content: string;        // HTML
  featuredImage?: {
    node: WpMediaItem;
  };
  author?: {
    node: WpAuthor;
  };
  categories?: {
    nodes: WpCategory[];
  };
  tags?: {
    nodes: Array<{ name: string; slug: string }>;
  };
}

// ─── Página ──────────────────────────────────────────────────────────────────

export interface WpPage {
  id: string;
  title: string;
  slug: string;
  content: string;        // HTML
  featuredImage?: {
    node: WpMediaItem;
  };
}

// ─── Respuestas paginadas ─────────────────────────────────────────────────────

export interface WpPageInfo {
  hasNextPage: boolean;
  hasPreviousPage: boolean;
  startCursor: string;
  endCursor: string;
}

export interface WpPostsResponse {
  posts: {
    nodes: WpPost[];
    pageInfo: WpPageInfo;
  };
}

export interface WpPostResponse {
  post: WpPost | null;
}

export interface WpPageResponse {
  page: WpPage | null;
}

export interface WpPagesResponse {
  pages: {
    nodes: WpPage[];
  };
}
