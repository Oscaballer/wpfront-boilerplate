import { revalidatePath, revalidateTag } from 'next/cache';
import { NextRequest } from 'next/server';

/**
 * Route handler para revalidación bajo demanda (ISR).
 * Útil para recibir webhooks desde WordPress cuando un post/página se actualiza.
 *
 * Configuración en WordPress (ej. plugin WP Webhooks o código custom):
 * URL: https://tu-dominio.com/api/revalidate
 * Method: POST
 * Headers: { "x-revalidate-secret": "TU_SECRETO" }
 * Body: { "type": "post", "slug": "mi-nueva-noticia" }
 */
export async function POST(req: NextRequest) {
  try {
    const secret = req.headers.get('x-revalidate-secret');
    
    // Verifica que el token enviado coincida con tu variable de entorno
    if (secret !== process.env.REVALIDATE_SECRET) {
      return Response.json({ message: 'Unauthorized' }, { status: 401 });
    }

    const body = await req.json();
    const { type, slug, tag } = body;

    // Si se provee un tag, revalida por tag
    if (tag) {
      revalidateTag(tag, 'max');
      return Response.json({ revalidated: true, now: Date.now(), tag });
    }

    // Revalidación por ruta basada en el tipo de contenido
    if (slug) {
      if (type === 'post') {
        revalidatePath(`/noticias/${slug}`);
        // También revalida el listado principal para que aparezca la nueva noticia
        revalidatePath('/noticias');
      } else if (type === 'page') {
        revalidatePath(`/${slug}`);
      } else {
        // Revalida globalmente si no se especifica tipo
        revalidatePath(`/${slug}`);
      }
      return Response.json({ revalidated: true, now: Date.now(), slug, type });
    }

    return Response.json({ message: 'Missing slug or tag' }, { status: 400 });

  } catch (err) {
    console.error('Revalidation error:', err);
    return Response.json({ message: 'Error revalidating' }, { status: 500 });
  }
}
