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
│   ├── globals.css             # Design Tokens y reset global
│   ├── providers.tsx           # Context Providers (React Query)
│   └── layout.tsx              # Root Layout con Providers
├── components/                 # Componentes base reutilizables
│   ├── Container/              # Wrapper de ancho máximo
│   ├── PostCard/               # Tarjeta de post para listados
│   └── Seo/                    # Helpers de metadatos dinámicos
├── lib/                        # Lógica de negocio y utilidades
│   ├── graphql/
│   │   ├── client.ts           # Cliente GraphQL singleton
│   │   └── queries/            # Consultas de Posts y Páginas
│   ├── types/                  # Tipados de WordPress
│   └── utils/                  # Helpers (formatDate, stripHtml, etc.)
├── public/                     # Assets estáticos
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
- `NEXT_PUBLIC_WORDPRESS_URL`: URL base de tu WordPress (ej: `https://mi-cms.com`).

### 4. Desarrollo
```bash
npm run dev
```

---

## 📦 Despliegue (Docker & GCP)

El proyecto incluye un `Dockerfile` optimizado y un workflow de GitHub Actions en `.github/workflows/deploy.yml`.

### Build Local
```bash
docker build --build-arg NEXT_PUBLIC_WORDPRESS_URL=https://tu-wp.com -t wpbp:latest .
```

### GitHub Actions (CI/CD)
Para habilitar el despliegue automático a GCP Artifact Registry, configura los siguientes **Secrets** en tu repositorio de GitHub:
- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT`
- `NEXT_PUBLIC_WORDPRESS_URL`

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

### Componentes base
Encapsula tu contenido en el `Container` para mantener la consistencia:
```tsx
import Container from '@/components/Container/Container';

<Container size="lg">
  {/* Tu contenido aquí */}
</Container>
```
