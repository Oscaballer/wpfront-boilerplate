import Link from "next/link";
import Image from "next/image";
import DOMPurify from "isomorphic-dompurify";
import styles from "./NewsCard.module.css";

interface NewsCardProps {
  post: {
    id: string;
    title: string;
    slug: string;
    date: string;
    excerpt: string;
    featuredImage?: {
      node: {
        sourceUrl: string;
        altText: string;
        mediaDetails?: {
          width: number;
          height: number;
        };
      };
    } | null;
    categories?: {
      nodes: { name: string }[];
    } | null;
  };
}

export default function NewsCard({ post }: NewsCardProps) {
  const date = new Date(post.date).toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const category = post.categories?.nodes?.[0]?.name || "Noticia";

  return (
    <article className={styles.card}>
      {/* Overlaid Link for the whole card */}
      <Link href={`/noticias/${post.slug}`} className={styles.cardLink} aria-hidden="true" tabIndex={-1}>
        &nbsp;
      </Link>

      <div className={styles.imageWrapper}>
        {post.featuredImage ? (
          <Image 
            src={post.featuredImage.node.sourceUrl} 
            alt={post.featuredImage.node.altText || post.title} 
            width={post.featuredImage.node.mediaDetails?.width || 600}
            height={post.featuredImage.node.mediaDetails?.height || 400}
            className={styles.image}
            loading="lazy"
          />
        ) : (
          <div className={styles.image} />
        )}
      </div>

      <div className={styles.content}>
        <div className={styles.meta}>
          <span className={styles.category}>{category}</span>
          <span>{date}</span>
        </div>
        <h2 className={styles.title}>
          <Link href={`/noticias/${post.slug}`}>
            {post.title}
          </Link>
        </h2>
        <div 
          className={styles.excerpt} 
          dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(post.excerpt) }} 
        />
        <span className={styles.readMore} aria-hidden="true">
          Leer más →
        </span>
      </div>
    </article>
  );
}
