import { graphqlClient } from "@/lib/graphql/client";
import { GET_POSTS } from "@/lib/graphql/queries/posts";
import NewsCard from "@/components/NewsCard/NewsCard";
import styles from "./page.module.css";
import Pagination from "@/components/Pagination/Pagination";
import type { Metadata } from "next";
import type { WpPostsResponse } from "@/lib/types/wordpress";
import { Suspense } from "react";

export const revalidate = 86400; // mantener cache hasta 24h, pero permitir invalidación manual antes

export const metadata: Metadata = {
  title: "Noticias",
  description: "Últimas noticias y actualizaciones.",
};

const PAGE_SIZE = 12;

interface PageProps {
  searchParams: Promise<{ after?: string; before?: string }>;
}

async function getPosts(variables: {
  first?: number;
  after?: string;
  last?: number;
  before?: string;
}) {
  const data = await graphqlClient.request<WpPostsResponse>(GET_POSTS, variables, { tags: ['posts'] });
  return data.posts;
}

export default async function NoticiasPage({ searchParams }: PageProps) {
  const params = await searchParams;

  // Si viene "before" vamos hacia atrás (last + before), si no, hacia adelante (first + after)
  const variables = params.before
    ? { last: PAGE_SIZE, before: params.before }
    : { first: PAGE_SIZE, after: params.after };

  const { nodes: posts, pageInfo } = await getPosts(variables);

  return (
    <main className="section container fade-in">
      <header className={styles.header}>
        <h1 className="section-title">Noticias</h1>
        <p className={`section-subtitle ${styles.subtitle}`}>Mantente al día con nuestras últimas novedades.</p>
      </header>

      {posts.length === 0 ? (
        <p>No hay noticias publicadas en este momento.</p>
      ) : (
        <>
          <div className={styles.grid}>
            {posts.map((post) => (
              <NewsCard key={post.id} post={post} />
            ))}
          </div>
          <Suspense fallback={<div className={styles.paginationLoading}>Cargando...</div>}>
            <Pagination pageInfo={pageInfo} basePath="/noticias" />
          </Suspense>
        </>
      )}
    </main>
  );
}
