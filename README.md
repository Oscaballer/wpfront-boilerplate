# WPFront Boilerplate — Next.js & Headless WordPress Starter Kit

Este es un Starter Kit/Boilerplate moderno y de alto rendimiento construido con **Next.js (App Router)**, diseñado para conectarse con un backend de **WordPress Headless** mediante GraphQL, Algolia Search y Google Cloud Storage (GCP).

Está optimizado para velocidad (Core Web Vitals), accesibilidad, indexación SEO, modularidad estética con CSS Modules, y despliegues robustos en producción mediante Docker Standalone.

---

## 🚀 Características Principales

- **Next.js (App Router)**: Aprovecha las últimas características de React y Server Components para un renderizado híbrido rápido.
- **Data Fetching Eficiente**: Cliente GraphQL nativo con `fetch` y soporte para revalidación bajo demanda mediante etiquetas (`revalidateTag`).
- **Búsqueda Instantánea con Algolia**: Buscador modal integrado (`react-instantsearch`) con soporte para atajos de teclado (`Ctrl+K`), navegación por teclado, y sugerencias rápidas.
- **Sincronización Automática Algolia-WordPress**: Ruta de API integrada (`/api/algolia/sync`) para sincronizar de forma masiva o selectiva contenidos desde WordPress hacia índices de Algolia.
- **Portal de Transparencia & Gestor GCP**: Página de documentos públicos que renderiza dinámicamente un árbol de archivos alojados en un bucket público de Google Cloud Storage (GCP), estructurado en directorios de manera automática.
- **Plugin de WordPress Integrado (`wp-plugins/transparencia-manager`)**: Permite explorar, subir, y eliminar archivos en el Bucket de GCP directamente desde el panel de administración de WordPress.
- **Sincronización de Estilos en Gutenberg (`wp-integration.php`)**: Plugin WordPress para inyectar los Design Tokens del frontend Next.js (colores, tipografía, reset, espaciado) en el editor Gutenberg, garantizando una experiencia visual idéntica (WYSIWYG) en el CMS.
- **Animaciones al Scroll**: Utilidades basadas en `IntersectionObserver` y hooks de React para realizar animaciones fluidas y animadores numéricos animados.
- **Multilenguaje Configurable (i18n)**: Soporte internacional configurable mediante middleware inteligente en `proxy.ts`, permitiendo habilitar o deshabilitar idiomas sin modificar la estructura del sitio.
- **Optimización de Producción**: Dockerfile optimizado en modo `standalone` con multi-stage builds y ejecución segura sin privilegios de root.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Framework | Next.js (App Router, standalone) |
| UI Library | React / React DOM |
| Data Fetching | Native `fetch` (Next.js Tag-based ISR Cache) |
| Búsqueda | Algolia Search (`algoliasearch`, `react-instantsearch`) |
| Lenguaje | TypeScript |
| Estilos | CSS Modules (Vanilla CSS) |
| CMS | WordPress (Headless + WPGraphQL) |
| Contenedor | Docker (Multi-stage) |

---

## 📁 Estructura del Proyecto

```
wpfront-boilerplate/
├── .github/workflows/          # Workflows de CI/CD (GitHub Actions)
├── app/                        # Rutas, layouts y páginas (Next.js App Router)
│   ├── [slug]/                 # Páginas dinámicas procedentes de WordPress
│   ├── noticias/               # Listados y artículos de noticias
│   ├── transparencia/          # Portal de visualización de archivos de GCP
│   ├── globals.css             # Estilos globales y Design Tokens base
│   ├── providers.tsx           # Proveedores de contexto globales
│   └── layout.tsx              # Layout raíz de la aplicación
├── components/                 # Componentes interactivos y visuales
│   ├── AlgoliaSearch/          # Buscador interactivo modal y triggers
│   ├── Header/                 # Menú jerárquico dinámico y contactos
│   ├── Footer/                 # Pie de página y enlaces parametrizados
│   ├── ScrollAnimations/       # Controladores de animación al hacer scroll
│   └── TransparenciaTree/      # Renderizador recursivo de carpetas/archivos
├── lib/                        # Capa de datos, consultas e integraciones
│   ├── algolia/                # Cliente y configuraciones de búsqueda
│   ├── graphql/                # Consultas y cliente GraphQL con ISR
│   └── utils/                  # Formateadores, helpers e i18n
├── public/                     # Recursos estáticos (Favicons, placeholders)
├── wp-plugins/                 # Plugins complementarios para instalar en WordPress
│   └── transparencia-manager/  # Administrador de archivos GCP en WordPress
├── wp-integration.php          # Plugin WP para inyectar estilos del frontend Next.js en Gutenberg
├── Dockerfile                  # Empaquetado Docker multi-stage optimizado para standalone
└── next.config.ts              # Configuración de Next.js
```

---

## 🏁 Inicio Rápido

### 1. Requisitos Previos
- Node.js ≥ 20.
- Instancia activa de WordPress con los plugins **WPGraphQL** y **WPGraphQL JWT Authentication** (si es requerido).
- Cuenta activa en **Algolia** y un Bucket público en **Google Cloud Storage** (opcional si no se usa el Portal de Transparencia).

### 2. Instalación
Instala las dependencias locales del proyecto:
```bash
npm install
```

### 3. Configuración (.env)
Copia el archivo de ejemplo y configura las variables según las necesidades de tu entorno:
```bash
cp .env.local.example .env.local
```

Define las URLs de tu WordPress, credenciales de Algolia, el nombre del Bucket de GCP y las variables de branding generales (`NEXT_PUBLIC_SITE_NAME`, `NEXT_PUBLIC_CONTACT_EMAIL`, etc.).

### 4. Desarrollo Local
Inicia el servidor de desarrollo en modo caliente:
```bash
npm run dev
```
La aplicación estará disponible en [http://localhost:3000](http://localhost:3000).

---

## 📦 Producción y Despliegue

### Compilación y Ejecución Local (Modo Standalone)
Puedes compilar y probar localmente el bundle optimizado idéntico al de producción:
```bash
npm run build
npm run start
```

### Contenedorización con Docker
El proyecto compila en Docker utilizando un pipeline optimizado en dos fases (multi-stage) reduciendo significativamente el peso de la imagen final:
```bash
docker build \
  --build-arg WORDPRESS_URL=https://tu-cms.com \
  --build-arg NEXT_PUBLIC_WORDPRESS_URL=https://tu-cms.com \
  -t wpfront-boilerplate:latest .
```

---

## ⚙️ Integración de Estilos en WordPress Gutenberg (`wp-integration.php`)

Para lograr una experiencia WYSIWYG (lo que ves es lo que obtienes) real, este boilerplate incluye un plugin que sincroniza el editor de WordPress con los estilos del frontend Next.js.

### Pasos para instalar:
1. Sube el archivo `wp-integration.php` al directorio `/wp-content/plugins/` de tu WordPress o comprímelo en un `.zip` e instálalo desde el panel de WordPress (*Plugins > Añadir nuevo > Subir plugin*).
2. Activa el plugin.
3. Edita los estilos y tokens definidos en el plugin (como colores y fuentes) para reflejar los de tu frontend. Gutenberg los cargará dinámicamente en el backend.

---

## 📂 Administrador de Archivos GCP (`wp-plugins/transparencia-manager`)

El plugin ubicado en `wp-plugins/transparencia-manager/` permite administrar archivos alojados en un bucket público de Google Cloud Storage directamente desde el panel de WordPress.

### Paso a paso para la Instalación y Configuración:

1. **Empaquetar e Instalar**:
   - Comprime la carpeta `wp-plugins/transparencia-manager/` en un archivo `.zip` y súbelo a WordPress (*Plugins > Añadir nuevo > Subir plugin*).
   - Activa el plugin.

2. **Crear una Cuenta de Servicio en GCP**:
   - Ve a la consola de Google Cloud, entra a **IAM y administración > Cuentas de servicio** y crea una cuenta.
   - Asígnale el rol **Administrador de objetos de Storage** (`roles/storage.objectAdmin`) para otorgarle permisos de lectura, escritura y eliminación sobre el Bucket.
   - Genera una clave JSON y descárgala.

3. **Configurar el Plugin en WordPress**:
   - En el menú lateral de WordPress aparecerá la sección **Transparencia**. Ve al submenú **Ajustes GCP**.
   - Introduce el **Nombre del Bucket GCP** (ej. `media.misitio.com`).
   - Copia y pega el contenido del archivo JSON de la clave descargada en **Service Account JSON**.
   - Guarda los cambios. El plugin ya está listo para operar sobre el bucket directamente.

---

## ⚙️ Revalidación de Contenido bajo Demanda (ISR)

Para vaciar la caché de Next.js y reflejar los cambios instantáneamente cuando se edita un post o página en WordPress, realiza una petición HTTP POST al endpoint de revalidación configurado:
- **Endpoint**: `https://tu-dominio.com/api/revalidate`
- **Cabecera obligatoria**: `x-revalidate-secret: <REVALIDATE_SECRET>`
- **Cuerpo JSON**:
  ```json
  {
    "type": "post", // O 'page'
    "slug": "slug-de-la-noticia-o-pagina"
  }
  ```
