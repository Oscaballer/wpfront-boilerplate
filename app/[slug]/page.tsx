import { notFound } from "next/navigation";
import Image from "next/image";
import { cache } from "react";
import DOMPurify from "isomorphic-dompurify";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_PAGE_BY_SLUG, getAllPages } from "@/lib/graphql/queries/pages";
import { getLocale } from "@/lib/utils/i18n";
import { fixWordPressImages, fixWordPressUrl } from "@/lib/utils/content";
import { Locale } from "@/i18n.config";
import styles from "./page.module.css";
import type { Metadata } from "next";

export const revalidate = false; // Solo revalidación on-demand vía webhook

export async function generateStaticParams() {
  try {
    const pages = await getAllPages({ tags: ['pages'] });
    return pages.map((page) => ({
      slug: page.slug,
    }));
  } catch (error) {
    console.warn("Could not fetch page slugs for static generation during build:", error);
    return [];
  }
}

interface PageData {
  page: {
    title: string;
    content: string;
    date: string;
    featuredImage?: {
      node: {
        sourceUrl: string;
        altText: string;
        mediaDetails?: {
          width: number;
          height: number;
        };
      };
    };
  } | null;
}

interface Props {
  params: Promise<{ slug: string }>;
}

const getPage = cache(async (slug: string, locale: Locale) => {
  // Errores de red se propagan para que Next.js ISR sirva caché stale
  const data = await graphqlClient.request<PageData>(GET_PAGE_BY_SLUG, { slug }, { tags: [`page-${slug}`] });
  return data.page; // null solo si la página no existe en WP (GraphQL responde ok)
});

export async function generateMetadata(
  { params }: Props
): Promise<Metadata> {
  const { slug } = await params;
  const locale = await getLocale();
  const page = await getPage(slug, locale);

  if (!page) {
    return { title: "Página no encontrada" };
  }

  return {
    title: page.title,
  };
}

export default async function Page({ params }: Props) {
  const { slug } = await params;
  const locale = await getLocale();
  const page = await getPage(slug, locale);

  if (!page) {
    notFound();
  }

  return (
    <main className="section container fade-in">
      {page.featuredImage && (
        <Image
          src={fixWordPressUrl(page.featuredImage.node.sourceUrl)}
          alt={page.featuredImage.node.altText || page.title}
          width={page.featuredImage.node.mediaDetails?.width || 1200}
          height={page.featuredImage.node.mediaDetails?.height || 630}
          className={styles.featuredImage}
          priority={true}
        />
      )}
      <h1 className="section-title" style={{ marginTop: '2rem', marginBottom: '2rem' }}>{page.title}</h1>
      <div
        className="content"
        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(fixWordPressImages(page.content)) }}
      />
    </main>
  );
}
