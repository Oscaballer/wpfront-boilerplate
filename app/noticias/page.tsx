import { graphqlClient } from "@/lib/graphql/client";
import { GET_POSTS } from "@/lib/graphql/queries/posts";
import NewsCard from "@/components/NewsCard/NewsCard";
import { getLocale } from "@/lib/utils/i18n";
import { Locale } from "@/i18n.config";
import styles from "./page.module.css";
import type { Metadata } from "next";
import type { WpPostsResponse } from "@/lib/types/wordpress";

export const metadata: Metadata = {
  title: "Noticias",
  description: "Últimas noticias y actualizaciones.",
};


async function getPosts(locale: Locale) {
  try {
    const data = await graphqlClient.request<WpPostsResponse>(GET_POSTS, { 
      first: 12, 
      language: locale.toUpperCase() 
    });
    return data.posts.nodes;
  } catch (error) {
    console.error("Error fetching posts:", error);
    return [];
  }
}

export default async function NoticiasPage() {
  const locale = await getLocale();
  const posts = await getPosts(locale);

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <h1 className={styles.title}>Noticias</h1>
        <p className={styles.subtitle}>Mantente al día con nuestras últimas novedades.</p>
      </header>

      {posts.length === 0 ? (
        <p>No hay noticias publicadas en este momento.</p>
      ) : (
        <div className={styles.grid}>
          {posts.map((post) => (
            <NewsCard key={post.id} post={post} />
          ))}
        </div>
      )}
    </div>
  );
}
