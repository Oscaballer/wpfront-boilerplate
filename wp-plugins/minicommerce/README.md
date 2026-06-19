# MiniCommerce — Plugin WordPress

Catálogo headless para Eros Sex Shop. Administra productos en `cms.eros.com.py`; el frontend Next.js en `eros.com.py/tienda` consume los datos vía WPGraphQL.

## Instalación

1. Comprimir esta carpeta en un `.zip` o copiarla a `wp-content/plugins/minicommerce/`.
2. Activar el plugin en WordPress.
3. Ir a **MiniCommerce → Ajustes GCP** y configurar:
   - Bucket: `media.eros.com.py`
   - URL pública: `https://media.eros.com.py`
   - Service Account JSON (rol `storage.objectAdmin`)
4. Ir a **MiniCommerce → Ajustes** y configurar:
   - Número WhatsApp para pedidos
   - URL y secret de revalidación ISR (`https://eros.com.py/api/revalidate`)

## Estructura GCP

Las imágenes se almacenan en:

```
gs://media.eros.com.py/minicommerce/catalogo/{filename}
```

URL pública:

```
https://media.eros.com.py/minicommerce/catalogo/{filename}
```

## Tablas

Al activar el plugin se crean:

- `{prefix}mc_articulo`
- `{prefix}mc_seccion`
- `{prefix}mc_sub_categoria`
- `{prefix}mc_imagen_articulo`

## Requisitos

- WordPress 6.x
- PHP 8.0+ con extensión OpenSSL
- WPGraphQL (fase 4)
- Bucket GCP público para lectura de imágenes

## Desarrollo

Este plugin vive en `wpfront-eros/wp-plugins/minicommerce/` del monorepo de referencia y se empaqueta para el CMS de producción.
