'use client';

import React, { useState, useEffect } from 'react';
import { InstantSearch, SearchBox, Hits, Highlight, Snippet } from 'react-instantsearch';
import type { Hit } from 'instantsearch.js';
import { searchClient, algoliaIndexName } from '@/lib/algolia/client';
import { Search, X } from 'lucide-react';
import styles from './SearchModal.module.css';

interface AlgoliaHit {
  objectID: string;
  post_title: string;
  permalink: string;
  content?: string;
  _highlightResult?: Record<string, unknown>;
  _snippetResult?: Record<string, unknown>;
}

interface HitProps {
  hit: Hit<AlgoliaHit>;
}

const HitComponent = ({ hit }: HitProps) => {
  return (
    <article className={styles.hit}>
      <a href={hit.permalink} className={styles.hitLink}>
        <div className={styles.hitContent}>
          <h3 className={styles.hitTitle}>
            <Highlight attribute="post_title" hit={hit} />
          </h3>
          <div className={styles.hitSnippet}>
            {/* Si usas WP Search with Algolia, content suele tener extractos */}
            <Snippet attribute="content" hit={hit} />
          </div>
        </div>
      </a>
    </article>
  );
};

export default function SearchModal() {
  const [isOpen, setIsOpen] = useState(false);

  // Escuchar shortcut Ctrl+K / Cmd+K
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        setIsOpen((prev) => !prev);
      }
      if (event.key === 'Escape' && isOpen) {
        setIsOpen(false);
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen]);

  // Si no hay cliente configurado, mostramos una versión placeholder del botón
  if (!searchClient) {
    return (
      <button 
        className={styles.triggerBtn} 
        onClick={() => alert("Algolia no está configurado en .env.local")}
        aria-label="Buscar (No configurado)"
      >
        <Search size={18} aria-hidden="true" />
        <span className={styles.placeholderText}>Buscar...</span>
        <kbd className={styles.shortcut} aria-hidden="true">⌘K</kbd>
      </button>
    );
  }

  return (
    <>
      <button 
        className={styles.triggerBtn} 
        onClick={() => setIsOpen(true)}
        aria-label="Abrir búsqueda"
        aria-keyshortcuts="Control+K"
      >
        <Search size={18} aria-hidden="true" />
        <span className={styles.placeholderText}>Buscar...</span>
        <kbd className={styles.shortcut} aria-hidden="true">⌘K</kbd>
      </button>

      {isOpen && (
        <div className={styles.overlay} onClick={() => setIsOpen(false)}>
          <div 
            className={styles.modal} 
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-label="Buscador de contenido"
          >
            <div className={styles.header}>
              <h2 className="sr-only">Buscador</h2>
              <button 
                className={styles.closeBtn} 
                onClick={() => setIsOpen(false)}
                aria-label="Cerrar búsqueda"
              >
                <X size={20} aria-hidden="true" />
              </button>
            </div>
            
            <div className={styles.searchContainer}>
              <InstantSearch 
                searchClient={searchClient} 
                indexName={algoliaIndexName}
              >
                <SearchBox 
                  placeholder="Busca artículos, guías..." 
                  classNames={{
                    root: styles.searchBoxRoot,
                    form: styles.searchBoxForm,
                    input: styles.searchBoxInput,
                    submit: styles.searchBoxSubmit,
                    reset: styles.searchBoxReset,
                  }}
                  autoFocus
                />
                <div className={styles.hitsContainer}>
                  <Hits 
                    hitComponent={HitComponent} 
                    classNames={{
                      root: styles.hitsRoot,
                      list: styles.hitsList,
                      item: styles.hitsItem,
                    }}
                  />
                </div>
              </InstantSearch>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
