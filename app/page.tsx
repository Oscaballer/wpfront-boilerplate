/**
 * app/page.tsx — Home del boilerplate
 *
 * Esta página es el punto de partida cuando clonas el boilerplate.
 * Reemplázala con tu propio contenido real una vez configurado.
 */
import styles from "./page.module.css";
import SearchModal from "@/components/AlgoliaSearch/SearchModal";

const STACK = [
  "Next.js 16",
  "React 19",
  "TypeScript",
  "WPGraphQL",
  "Algolia",
  "CSS Modules",
  "TanStack Query",
  "Docker",
  "GCP",
];

export default function Home() {
  const wpUrl = process.env.WORDPRESS_URL ?? process.env.NEXT_PUBLIC_WORDPRESS_URL;
  const isConfigured = !!wpUrl && wpUrl !== "https://TU_WORDPRESS_URL_AQUI";

  return (
    <main className={styles.page}>
      {/* Header flotante para demo del buscador */}
      <div style={{ position: 'absolute', top: '1rem', right: '1rem' }}>
        <SearchModal />
      </div>

      <section className={styles.hero}>
        {/* Badge */}
        <p className={styles.badge}>Boilerplate listo para usar</p>

        {/* Título */}
        <h1 className={styles.title}>Next.js + Headless WordPress</h1>

        <p className={styles.subtitle}>
          Stack completo para construir sitios rápidos con WordPress como CMS
          headless, compilados en Docker y desplegados en GCP.
        </p>

        {/* Stack */}
        <div className={styles.stack}>
          {STACK.map((item) => (
            <span key={item} className={styles.tag}>
              {item}
            </span>
          ))}
        </div>

        {/* Checklist de configuración */}
        <div className={styles.checklist}>
          <h2>Pasos para configurar el proyecto</h2>
          <ol>
            <li>
              Copia <code>.env.local.example</code> a{" "}
              <code>.env.local</code> y completa{" "}
              <code>NEXT_PUBLIC_WORDPRESS_URL</code>
            </li>
            <li>
              Instala el plugin{" "}
              <strong>WPGraphQL</strong> en tu WordPress y actívalo.
              Verifica en <code>/graphql</code>.
            </li>
            <li>
              Configura los <strong>secrets de GitHub</strong> para el
              workflow de CI/CD (ver <code>.github/workflows/deploy.yml</code>).
            </li>
            <li>
              Reemplaza este <code>app/page.tsx</code> con el diseño real de tu
              sitio usando los componentes de <code>components/</code>.
            </li>
          </ol>
        </div>

        {/* Estado de configuración */}
        <p className={styles.footer}>
          {isConfigured ? (
            <>
              ✅ WordPress configurado en{" "}
              <a href={`${wpUrl}/graphql`} target="_blank" rel="noopener noreferrer">
                {wpUrl}/graphql
              </a>
            </>
          ) : (
            "⚠️ WordPress no configurado — completa NEXT_PUBLIC_WORDPRESS_URL en .env.local"
          )}
        </p>
      </section>
    </main>
  );
}
