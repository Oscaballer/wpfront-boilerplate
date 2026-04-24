import { notFound } from "next/navigation";
import Image from "next/image";
import { cache } from "react";
import DOMPurify from "isomorphic-dompurify";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_PAGE_BY_SLUG, getAllPages } from "@/lib/graphql/queries/pages";
import { getLocale } from "@/lib/utils/i18n";
import { Locale } from "@/i18n.config";
import styles from "./page.module.css";
import type { Metadata } from "next";

export const revalidate = 3600; // Revalidar cada hora

export async function generateStaticParams() {
  const pages = await getAllPages();
  return pages.map((page) => ({
    slug: page.slug,
  }));
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
  try {
    const data = await graphqlClient.request<PageData>(GET_PAGE_BY_SLUG, { slug, language: locale.toUpperCase() });
    return data.page;
  } catch (error) {
    console.error("Error fetching page:", error);
    return null;
  }
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
    <div className={styles.container}>
      {page.featuredImage && (
        <Image 
          src={page.featuredImage.node.sourceUrl} 
          alt={page.featuredImage.node.altText || page.title}
          width={page.featuredImage.node.mediaDetails?.width || 1200}
          height={page.featuredImage.node.mediaDetails?.height || 630}
          className={styles.featuredImage}
          priority={true}
        />
      )}
      <h1 className={styles.title}>{page.title}</h1>
      <div 
        className={styles.content}
        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(page.content) }} 
      />
    </div>
  );
}
