import Link from "next/link";
import Image from "next/image";
import DOMPurify from "isomorphic-dompurify";
import { fixWordPressUrl } from "@/lib/utils/content";
import styles from "./NewsCard.module.css";
import compactStyles from "./NewsCardCompact.module.css";

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
  variant?: "default" | "compact";
}

export default function NewsCard({ post, variant = "default" }: NewsCardProps) {
  const date = new Date(post.date).toLocaleDateString("es-ES", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });

  const category = post.categories?.nodes?.[0]?.name || "Noticia";

  if (variant === "compact") {
    return (
      <article className={compactStyles.item}>
        <Link
          href={`/noticias/${post.slug}`}
          className={compactStyles.imageWrapper}
          tabIndex={-1}
          aria-hidden="true"
        >
          {post.featuredImage ? (
            <Image
              src={fixWordPressUrl(post.featuredImage.node.sourceUrl)}
              alt={post.featuredImage.node.altText || post.title}
              width={160}
              height={110}
              className={compactStyles.image}
              loading="lazy"
            />
          ) : (
            <div className={compactStyles.image} />
          )}
        </Link>

        <div className={compactStyles.body}>
          <div className={compactStyles.meta}>
            <time className={compactStyles.date}>{date}</time>
          </div>
          <h4 className={compactStyles.title}>
            <Link href={`/noticias/${post.slug}`} className={compactStyles.titleLink}>
              {post.title}
            </Link>
          </h4>
          <div
            className={compactStyles.excerpt}
            dangerouslySetInnerHTML={{
              __html: DOMPurify.sanitize(post.excerpt),
            }}
          />
        </div>
      </article>
    );
  }

  return (
    <article className={styles.card}>
      <Link
        href={`/noticias/${post.slug}`}
        className={styles.cardLink}
        aria-label={`Leer noticia: ${post.title}`}
      />

      <div className={styles.imageWrapper}>
        {post.featuredImage ? (
          <Image
            src={fixWordPressUrl(post.featuredImage.node.sourceUrl)}
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
          <time className={styles.metaDate}>{date}</time>
        </div>

        <h2 className={styles.title}>
          <Link href={`/noticias/${post.slug}`} className={styles.titleLink}>
            {post.title}
          </Link>
        </h2>

        <div
          className={styles.excerpt}
          dangerouslySetInnerHTML={{
            __html: DOMPurify.sanitize(post.excerpt),
          }}
        />

        <footer className={styles.cardFooter}>
          <Link href={`/noticias/${post.slug}`} className={styles.titleLink}>
            <span className={styles.readMore} aria-hidden="true">
              Leer más
              <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="M5 12h14M12 5l7 7-7 7" />
              </svg>
            </span>
          </Link>

        </footer>
      </div>
    </article>
  );
}