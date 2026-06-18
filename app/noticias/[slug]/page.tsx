import { notFound } from "next/navigation";
import Image from "next/image";
import { cache } from "react";
import DOMPurify from "isomorphic-dompurify";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_POST_BY_SLUG, getAllPostSlugs } from "@/lib/graphql/queries/posts";
import { getLocale } from "@/lib/utils/i18n";
import { fixWordPressImages, fixWordPressUrl } from "@/lib/utils/content";
import { Locale } from "@/i18n.config";
import styles from "./page.module.css";
import type { Metadata } from "next";

export const revalidate = false; // Solo revalidación on-demand vía webhook

export async function generateStaticParams() {
  try {
    const posts = await getAllPostSlugs({ tags: ['posts'] });
    return posts.map((post) => ({
      slug: post.slug,
    }));
  } catch (error) {
    console.warn("Could not fetch post slugs for static generation during build:", error);
    return [];
  }
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
  // Errores de red se propagan para que Next.js ISR sirva caché stale
  const data = await graphqlClient.request<PostData>(GET_POST_BY_SLUG, {
    slug
  }, { tags: [`post-${slug}`] });
  return data.post; // null solo si el post no existe en WP (GraphQL responde ok)
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
    <article className="section container">
      {/* Header + Imagen lado a lado en desktop */}
      <div className={styles.hero}>
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
          <div className={styles.imageWrapper}>
            <Image
              src={fixWordPressUrl(post.featuredImage.node.sourceUrl)}
              alt={post.featuredImage.node.altText || post.title}
              width={700}
              height={440}
              className={styles.featuredImage}
              priority={true}
              quality={80}
            />
          </div>
        )}
      </div>

      <div
        className="content"
        dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(fixWordPressImages(post.content)) }}
      />
    </article>
  );
}
