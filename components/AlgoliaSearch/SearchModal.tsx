'use client';

import React, { useState, useEffect } from 'react';
import { InstantSearch, SearchBox, Hits, Highlight, Snippet, useInstantSearch } from 'react-instantsearch';
import type { Hit } from 'instantsearch.js';
import { searchClient, algoliaIndexName } from '@/lib/algolia/client';
import { Search, X, FileText, Newspaper, ArrowRight } from 'lucide-react';
import { formatDate } from '@/lib/utils/format';
import styles from './SearchModal.module.css';

interface AlgoliaHit {
  objectID: string;
  post_title: string;
  permalink: string;
  content?: string;
  type?: 'post' | 'page';
  date?: string;
  _highlightResult?: Record<string, any>;
  _snippetResult?: Record<string, any>;
}

interface HitProps {
  hit: Hit<AlgoliaHit>;
}

const HitComponent = ({ hit }: HitProps) => {
  const isPost = hit.type === 'post';
  return (
    <article className={styles.hit}>
      <a href={hit.permalink} className={styles.hitLink}>
        <div className={styles.hitIcon}>
          {isPost ? <Newspaper size={18} /> : <FileText size={18} />}
        </div>
        <div className={styles.hitContent}>
          <header className={styles.hitHeader}>
            <span className={`${styles.hitBadge} ${isPost ? styles.badgePost : styles.badgePage}`}>
              {isPost ? 'Noticia' : 'Página'}
            </span>
            {hit.date && (
              <time className={styles.hitDate}>
                {formatDate(hit.date, 'es', { day: 'numeric', month: 'short', year: 'numeric' })}
              </time>
            )}
          </header>
          <h3 className={styles.hitTitle}>
            <Highlight attribute="post_title" hit={hit} />
          </h3>
          <div className={styles.hitSnippet}>
            <Snippet attribute="content" hit={hit} />
          </div>
        </div>
        <div className={styles.hitArrow}>
          <ArrowRight size={16} />
        </div>
      </a>
    </article>
  );
};

const SUGGESTIONS = ['Noticias', 'Contacto', 'Páginas', 'Servicios', 'Ayuda'];

// ModalContent must live *inside* <InstantSearch> to access its hooks
const ModalContent = ({ onClose }: { onClose: () => void }) => {
  const { indexUiState, setIndexUiState } = useInstantSearch();
  const hasQuery = !!indexUiState.query;

  const handleSuggestion = (term: string) => {
    setIndexUiState((prev) => ({ ...prev, query: term }));
  };

  return (
    <>
      <div className={styles.modalHeader}>
        <div className={styles.searchIcon}>
          <Search size={20} />
        </div>
        <SearchBox
          placeholder="¿Qué estás buscando?"
          classNames={{
            root: styles.searchBoxRoot,
            form: styles.searchBoxForm,
            input: styles.searchBoxInput,
            submit: styles.searchBoxSubmit,
            reset: styles.searchBoxReset,
          }}
          autoFocus
        />
        <button
          className={styles.closeBtn}
          onClick={onClose}
          title="Cerrar (Esc)"
        >
          <X size={20} />
          <span className={styles.closeText}>ESC</span>
        </button>
      </div>

      <div className={styles.modalBody}>
        {!hasQuery ? (
          <div className={styles.emptyState}>
            <div className={styles.emptyIcon}>
              <Search size={40} strokeWidth={1} />
            </div>
            <p>Escribe algo para comenzar a buscar...</p>
            <div className={styles.suggestions}>
              <span>Sugerencias:</span>
              {SUGGESTIONS.map((term) => (
                <button key={term} onClick={() => handleSuggestion(term)}>
                  {term}
                </button>
              ))}
            </div>
          </div>
        ) : (
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
        )}
      </div>

      <footer className={styles.modalFooter}>
        <div className={styles.footerBranding}>
          Búsqueda potenciada por <span>Algolia</span>
        </div>
        <div className={styles.footerShortcuts}>
          <span><kbd>↑↓</kbd> Navegar</span>
          <span><kbd>↵</kbd> Seleccionar</span>
        </div>
      </footer>
    </>
  );
};

export default function SearchModal() {
  const [isOpen, setIsOpen] = useState(false);

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

  if (!searchClient) return null;

  return (
    <>
      {/* Trigger: fake search input que abre el modal */}
      <div className={styles.triggerWrapper} onClick={() => setIsOpen(true)} role="button" tabIndex={0} onKeyDown={(e) => e.key === 'Enter' && setIsOpen(true)} aria-label="Buscar en el portal">
        <input
          type="text"
          placeholder="Búsqueda rápida"
          readOnly
          className={styles.triggerInput}
          aria-hidden="true"
          id="search-input"
        />
        <button
          type="button"
          className={styles.triggerBtn}
          tabIndex={-1}
          aria-hidden="true"
          id="search-btn"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
        </button>
      </div>

      {isOpen && (
        <div className={styles.overlay} onClick={() => setIsOpen(false)}>
          <div
            className={styles.modal}
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-label="Buscador del portal"
          >
            <InstantSearch
              searchClient={searchClient}
              indexName={algoliaIndexName}
              future={{ preserveSharedStateOnUnmount: true }}
            >
              <ModalContent onClose={() => setIsOpen(false)} />
            </InstantSearch>
          </div>
        </div>
      )}
    </>
  );
}
