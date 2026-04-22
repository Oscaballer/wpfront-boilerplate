/**
 * components/Seo/generateMetadata.ts
 *
 * Helper para construir el objeto `Metadata` de Next.js 16 a partir de
 * datos de WordPress. Usa en generateMetadata() de tus page.tsx.
 *
 * @example
 * // app/blog/[slug]/page.tsx
 * export async function generateMetadata({ params }) {
 *   const { slug } = await params;
 *   const post = await getPostBySlug(slug);
 *   if (!post) return {};
 *   return buildPostMetadata(post, "https://tu-sitio.com");
 * }
 */
import type { Metadata } from "next";
import type { WpPost, WpPage } from "@/lib/types/wordpress";
import { stripHtml, truncate } from "@/lib/utils/format";

/**
 * Genera el objeto Metadata de Next.js para un Post de WordPress.
 */
export function buildPostMetadata(
  post: WpPost,
  siteUrl: string
): Metadata {
  const description = post.excerpt
    ? truncate(stripHtml(post.excerpt), 160)
    : undefined;

  const ogImage = post.featuredImage?.node.sourceUrl;
  const url = `${siteUrl}/blog/${post.slug}`;

  return {
    title: post.title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title: post.title,
      description,
      url,
      type: "article",
      publishedTime: post.date,
      modifiedTime: post.modified,
      images: ogImage ? [{ url: ogImage }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title: post.title,
      description,
      images: ogImage ? [ogImage] : undefined,
    },
  };
}

/**
 * Genera el objeto Metadata de Next.js para una Página de WordPress.
 */
export function buildPageMetadata(
  page: WpPage,
  siteUrl: string
): Metadata {
  const description = page.content
    ? truncate(stripHtml(page.content), 160)
    : undefined;

  const ogImage = page.featuredImage?.node.sourceUrl;
  const url = `${siteUrl}/${page.slug}`;

  return {
    title: page.title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title: page.title,
      description,
      url,
      type: "website",
      images: ogImage ? [{ url: ogImage }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title: page.title,
      description,
      images: ogImage ? [ogImage] : undefined,
    },
  };
}
