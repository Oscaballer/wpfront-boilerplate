# Next.js 16 + Headless WordPress Boilerplate

Este es un **Starter Kit / Boilerplate** de producción diseñado para arrancar rápidamente proyectos frontend conectados a WordPress mediante GraphQL. Está optimizado para rendimiento, escalabilidad y despliegue automatizado en Google Cloud Platform (GCP).

---

## 🚀 Características principales

- **Next.js 16 (App Router)**: Aprovechando las últimas mejoras de React 19 y Server Components.
- **Turbopack Optimized**: Configuración corregida para evitar warnings de workspace root en monorepos.
- **Headless WordPress Core**: Integración nativa con WPGraphQL para un consumo de datos eficiente.
- **TanStack Query (React Query) v5**: Configurado para SSR y Client-side fetching, incluyendo `ReactQueryDevtools`.
- **Sistema de Design Tokens (CSS Modules)**: Arquitectura de estilos basada en variables CSS configurables, con soporte nativo para **Dark Mode**.
- **Capa de Datos Tipada**: Tipos TypeScript completos para Posts, Páginas, Media y Autores de WordPress.
- **Menú Dinámico Jerárquico**: Generación automática de navegación multinivel con soporte de submenús renderizados sin Javascript extra (CSS puro).
- **Rutas Dinámicas Automáticas (`/[slug]`, `/noticias`)**: Integración directa de páginas estáticas y listado de noticias desde WordPress con formateo en tiempo real.
- **Soporte Multilenguaje (i18n) Activable**: Motor de internacionalización configurable a través de `proxy.ts` para habilitar/deshabilitar traducciones limpiamente (apoyo nativo a `$language` de WPGraphQL).
- **SEO Ready**: Helpers integrados para generar metadatos dinámicos, Open Graph y Twitter Cards automáticamente.
- **Infraestructura Docker**: Build multi-stage optimizado para producción (`standalone` mode).
- **CI/CD con GitHub Actions**: Workflow configurado para build y push automático a GCP Artifact Registry.

---

## 🛠️ Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Framework | Next.js (App Router) | 16.2.4 |
| UI Library | React | 19.2.4 |
| Lenguaje | TypeScript | ^5 |
| Estilos | CSS Modules | — |
| Data fetching | graphql-request + TanStack Query | ^7 / ^5 |
| CMS | WordPress headless (WPGraphQL) | — |
| Contenedor | Docker (multi-stage, standalone) | — |
| Deploy | GCP VPS via Artifact Registry | — |

---

## 📁 Estructura de carpetas

```
wpfront-boilerplate/
├── .github/workflows/          # CI/CD (GitHub Actions)
├── app/                        # Rutas, layouts y página de bienvenida
│   ├── [slug]/                 # Páginas dinámicas desde WordPress
│   ├── noticias/               # Rutas de listado y detalle de noticias
│   ├── globals.css             # Design Tokens y reset global
│   ├── providers.tsx           # Context Providers (React Query)
│   └── layout.tsx              # Root Layout con Providers
├── components/                 # Componentes base reutilizables
│   ├── Header/                 # Menú principal jerárquico
│   ├── LanguageSwitcher/       # Selector de idiomas
│   ├── NewsCard/               # Tarjeta de post para listados
│   └── Seo/                    # Helpers de metadatos dinámicos
├── lib/                        # Lógica de negocio y utilidades
│   ├── graphql/
│   │   ├── client.ts           # Cliente GraphQL singleton
│   │   └── queries/            # Consultas de Posts, Páginas y Menú
│   ├── types/                  # Tipados de WordPress
│   └── utils/                  # Helpers (formatDate, stripHtml, i18n, etc.)
├── public/                     # Assets estáticos
├── i18n.config.ts              # Configuración global del multilenguaje
├── proxy.ts                    # Motor de enrutamiento (i18n) y proxy (Next.js 16)
├── Dockerfile                  # Empaquetado para producción
└── next.config.ts              # Configuración de Next.js y Turbopack
```

---

## 🏁 Inicio rápido

### 1. Requisitos previos
- Node.js ≥ 20.
- WordPress con el plugin [WPGraphQL](https://www.wpgraphql.com/) activo.
- Docker (opcional, para despliegue).

### 2. Instalación
```bash
git clone <repo-url>
cd wpfront-boilerplate
npm install
```

### 3. Configuración
Copia el archivo de ejemplo y completa tus datos:
```bash
cp .env.local.example .env.local
```
**Variables esenciales:**
- `WORDPRESS_URL`: URL base de tu WordPress para consultas de servidor (Seguro, ej: `https://mi-cms.com`).
- `NEXT_PUBLIC_WORDPRESS_URL`: Solo si necesitas la URL en el cliente (opcional).
- `NEXT_PUBLIC_ALGOLIA_APP_ID`: Application ID de Algolia.
- `NEXT_PUBLIC_ALGOLIA_SEARCH_API_KEY`: Search-Only API Key de Algolia.
- `NEXT_PUBLIC_ALGOLIA_INDEX_NAME`: Nombre del índice.
- `REVALIDATE_SECRET`: Secreto para revalidación bajo demanda desde WordPress (ISR).

### 4. Desarrollo
```bash
npm run dev
```

---

## 📦 Despliegue (Docker & GCP)

El proyecto incluye un `Dockerfile` optimizado con **usuario no-root** para mayor seguridad y un workflow de GitHub Actions.

### Build Local
```bash
docker build \
  --build-arg WORDPRESS_URL=https://tu-wp.com \
  --build-arg NEXT_PUBLIC_WORDPRESS_URL=https://tu-wp.com \
  -t wpbp:latest .
```

### GitHub Actions (CI/CD)
Para habilitar el despliegue automático a GCP Artifact Registry, configura los siguientes **Secrets** en tu repositorio de GitHub:
- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT`
- `WORDPRESS_URL` (Seguridad: Usada en el servidor)
- `REVALIDATE_SECRET` (Seguridad: Usada para webhooks ISR)
- `NEXT_PUBLIC_WORDPRESS_URL` (Opcional: Usada en el cliente)

Y las siguientes **Variables**:
- `GCP_REGION`
- `GCP_PROJECT_ID`
- `GCP_AR_REPO`
- `GCP_IMAGE_NAME`

---

## 📖 Guía de uso

### Utilidades de formato
Usa `lib/utils/format.ts` para tareas comunes:
```ts
import { formatDate, stripHtml } from '@/lib/utils/format';

const date = formatDate(wpDate); // "15 de mayo de 2026"
const cleanText = stripHtml(wpContent);
```

### SEO dinámico
En tus páginas dinámicas (`[slug]/page.tsx`), usa el helper de SEO:
```ts
import { buildPostMetadata } from '@/components/Seo/generateMetadata';

export async function generateMetadata({ params }) {
  const post = await getPostBySlug((await params).slug);
  return buildPostMetadata(post, "https://tu-web.com");
}
```

### Revalidación bajo demanda (ISR)
El proyecto incluye un endpoint para revalidar contenido instantáneamente desde WordPress sin esperar al tiempo de caché:
- **URL**: `https://tu-dominio.com/api/revalidate`
- **Método**: `POST`
- **Headers**: `x-revalidate-secret: TU_REVALIDATE_SECRET`
- **Payload**:
```json
{
  "type": "post", 
  "slug": "mi-slug-actualizado"
}
```

#### Cómo configurarlo en WordPress:

**Opción A: Usando un Plugin (Recomendado)**
1. Instala el plugin **WP Webhooks**.
2. Ve a *Ajustes > WP Webhooks > Send Data*.
3. Selecciona el trigger `Post updated`.
4. Añade la URL de tu endpoint y el header `x-revalidate-secret`.

**Opción B: Código en functions.php**
Añade este fragmento al final de tu `functions.php` para disparar la revalidación automáticamente al guardar:

```php
add_action('save_post', 'wp_to_next_revalidate', 10, 3);
function wp_to_next_revalidate($post_id, $post, $update) {
    if (!$update || $post->post_status != 'publish') return;

    $url = 'https://tu-dominio.com/api/revalidate';
    $secret = 'TU_REVALIDATE_SECRET';

    wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-revalidate-secret' => $secret
        ],
        'body' => json_encode([
            'type' => $post->post_type == 'post' ? 'post' : 'page',
            'slug' => $post->post_name
        ])
    ]);
}
```

### Componentes base
Encapsula tu contenido en el `Container` para mantener la consistencia:
```tsx
import Container from '@/components/Container/Container';

<Container size="lg">
  {/* Tu contenido aquí */}
</Container>
```
