import { notFound } from "next/navigation";
import Image from "next/image";
import { cache } from "react";
import DOMPurify from "isomorphic-dompurify";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_POST_BY_SLUG, getAllPostSlugs } from "@/lib/graphql/queries/posts";
import { getLocale } from "@/lib/utils/i18n";
import { Locale } from "@/i18n.config";
import styles from "./page.module.css";
import type { Metadata } from "next";

export const revalidate = 3600; // Revalidar cada hora

export async function generateStaticParams() {
  const posts = await getAllPostSlugs();
  return posts.map((post) => ({
    slug: post.slug,
  }));
}

interface PostData {
  post: {
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
    author?: {
      node: {
        name: string;
      };
    };
    categories?: {
      nodes: {
        name: string;
      }[];
    };
  } | null;
}

interface Props {
  params: Promise<{ slug: string }>;
}

const getPost = cache(async (slug: string, locale: Locale) => {
  try {
    const data = await graphqlClient.request<PostData>(GET_POST_BY_SLUG, { 
      slug, 
      language: locale.toUpperCase() 
    });
    return data.post;
  } catch (error) {
    console.error("Error fetching post:", error);
    return null;
  }
});

export async function generateMetadata(
  { params }: Props
): Promise<Metadata> {
  const { slug } = await params;
  const locale = await getLocale();
  const post = await getPost(slug, locale);

  if (!post) {
    return { title: "Noticia no encontrada" };
  }

  return {
    title: post.title,
  };
}

export default async function PostPage({ params }: Props) {
  const { slug } = await params;
  const locale = await getLocale();
  const post = await getPost(slug, locale);

  if (!post) {
    notFound();
  }

  const date = new Date(post.date).toLocaleDateString(locale === 'es' ? 'es-ES' : 'en-US', {
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const category = post.categories?.nodes?.[0]?.name || "Noticia";
  const author = post.author?.node?.name || "Equipo";

  return (
    <article className={styles.container}>
      <header className={styles.header}>
        <h1 className={styles.title}>{post.title}</h1>
        <div className={styles.meta}>
          <span className={styles.category}>{category}</span>
          <span>{date}</span>
          <span>•</span>
          <span>Por {author}</span>
        </div>
      </header>

      {post.featuredImage && (
        <Image 
          src={post.featuredImage.node.sourceUrl} 
          alt={post.featuredImage.node.altText || post.title}
          width={post.featuredImage.node.mediaDetails?.width || 1200}
          height={post.featuredImage.node.mediaDetails?.height || 630}
          className={styles.featuredImage}
          priority={true}
        />
      )}

      <div 
        className={styles.content}
        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(post.content) }} 
      />
    </article>
  );
}
