/**
 * components/PostCard/PostCard.tsx
 *
 * Tarjeta de post de blog. Lista para usar en el listado de posts.
 * Recibe un WpPost parcial para ser flexible (listado vs. detalle).
 *
 * @example
 * <PostCard post={post} />
 */
import Image from "next/image";
import Link from "next/link";
import type { WpPost } from "@/lib/types/wordpress";
import { formatDate, truncate, getPostUrl } from "@/lib/utils/format";
import styles from "./PostCard.module.css";

interface PostCardProps {
  post: Pick<
    WpPost,
    "id" | "title" | "slug" | "date" | "excerpt" | "featuredImage" | "categories" | "author"
  >;
}

export default function PostCard({ post }: PostCardProps) {
  const { title, slug, date, excerpt, featuredImage, categories, author } = post;
  const category = categories?.nodes[0];
  const authorName = author?.node.name;
  const image = featuredImage?.node;
  const url = getPostUrl(slug);

  return (
    <article className={styles.card}>
      {/* Imagen destacada */}
      {image && (
        <Link href={url} className={styles.imageWrapper} tabIndex={-1}>
          <Image
            src={image.sourceUrl}
            alt={image.altText || title}
            fill
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
            className={styles.image}
          />
        </Link>
      )}

      {/* Cuerpo */}
      <div className={styles.body}>
        {/* Categoría */}
        {category && (
          <span className={styles.category}>{category.name}</span>
        )}

        {/* Título */}
        <h2 className={styles.title}>
          <Link href={url} className={styles.titleLink}>
            {title}
          </Link>
        </h2>

        {/* Excerpt */}
        {excerpt && (
          <p
            className={styles.excerpt}
            dangerouslySetInnerHTML={{
              __html: truncate(excerpt.replace(/<[^>]*>/g, ""), 120),
            }}
          />
        )}

        {/* Meta: autor + fecha */}
        <footer className={styles.meta}>
          {authorName && (
            <span className={styles.author}>{authorName}</span>
          )}
          <time dateTime={date} className={styles.date}>
            {formatDate(date)}
          </time>
        </footer>
      </div>
    </article>
  );
}
