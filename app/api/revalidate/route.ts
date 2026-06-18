import { revalidatePath, revalidateTag } from 'next/cache';
import { NextRequest } from 'next/server';

/**
 * Route handler para revalidación bajo demanda (ISR).
 * Recibe webhooks desde WordPress cuando un post/página se actualiza.
 *
 * Configuración en WordPress (plugin WP Webhooks o código custom):
 *   URL:  https://tu-dominio.com/api/revalidate
 *   Method: POST
 *   Headers: { "x-revalidate-secret": "TU_SECRETO" }
 *
 * Payloads soportados:
 *   Por tag:   { "tag": "posts" }
 *   Por tipo/slug:
 *     { "type": "post", "slug": "mi-noticia" }
 *     { "type": "page", "slug": "mi-pagina" }
 */
export async function POST(req: NextRequest) {
  try {
    const secret = req.headers.get('x-revalidate-secret');

    if (secret !== process.env.REVALIDATE_SECRET) {
      return Response.json({ message: 'Unauthorized' }, { status: 401 });
    }

    const body = await req.json();
    const { type, slug, tag } = body;

    // ── Revalidación por tag ──────────────────────────────────────────
    if (tag) {
      revalidateTag(tag, 'max');
      return Response.json({ revalidated: true, now: Date.now(), tag });
    }

    // ── Revalidación por tipo + slug ──────────────────────────────────
    if (slug && type) {
      if (type === 'post') {
        // Tags: purga el post individual y el listado del data cache
        revalidateTag(`post-${slug}`, 'max');
        revalidateTag('posts', 'max');
        // Paths: purga la ruta del post y el listado de noticias
        revalidatePath(`/noticias/${slug}`);
        revalidatePath('/noticias');
        return Response.json({ revalidated: true, now: Date.now(), slug, type });
      }

      if (type === 'page') {
        revalidateTag(`page-${slug}`, 'max');
        revalidatePath(`/${slug}`);
        return Response.json({ revalidated: true, now: Date.now(), slug, type });
      }

      // Fallback: solo path
      revalidatePath(`/${slug}`);
      return Response.json({ revalidated: true, now: Date.now(), slug, type });
    }

    return Response.json({ message: 'Missing slug, type, or tag' }, { status: 400 });

  } catch (err) {
    console.error('Revalidation error:', err);
    return Response.json({ message: 'Error revalidating' }, { status: 500 });
  }
}
