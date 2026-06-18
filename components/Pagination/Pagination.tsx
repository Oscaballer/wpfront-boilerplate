"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useMemo } from "react";
import type { WpPageInfo } from "@/lib/types/wordpress";
import styles from "./Pagination.module.css";

interface PaginationProps {
  pageInfo: WpPageInfo;
  basePath: string;
}

export default function Pagination({ pageInfo, basePath }: PaginationProps) {
  const searchParams = useSearchParams();

  const createPageUrl = useMemo(() => {
    return (cursor: string, direction: "after" | "before") => {
      const params = new URLSearchParams(searchParams.toString());
      // Eliminar siempre el parámetro opuesto para evitar conflictos
      const opposite = direction === "after" ? "before" : "after";
      params.delete(opposite);
      if (cursor) {
        params.set(direction, cursor);
      } else {
        params.delete(direction);
      }
      const query = params.toString();
      return query ? `${basePath}?${query}` : basePath;
    };
  }, [basePath, searchParams]);

  if (!pageInfo?.hasNextPage && !pageInfo?.hasPreviousPage) {
    return (
      <nav className={styles.pagination} aria-label="Paginación de noticias">
        <span className={styles.info}>Solo hay una página de resultados</span>
      </nav>
    );
  }

  return (
    <nav className={styles.pagination} aria-label="Paginación de noticias">
      {pageInfo?.hasPreviousPage && (
        <Link
          href={createPageUrl(pageInfo.startCursor, "before")}
          className={styles.link}
          rel="prev"
        >
          ← Anterior
        </Link>
      )}
      {pageInfo?.hasNextPage && (
        <Link
          href={createPageUrl(pageInfo.endCursor, "after")}
          className={styles.link}
          rel="next"
        >
          Siguiente →
        </Link>
      )}
    </nav>
  );
}