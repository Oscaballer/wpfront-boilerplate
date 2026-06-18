/**
 * lib/utils/content.ts
 *
 * Utilidades para procesar contenido proveniente de WordPress.
 */

const wpUrl = process.env.WORDPRESS_URL || 'https://cms.derechoune.edu.py';
const bucketUrl = 'https://media.derechoune.edu.py';

/**
 * Reemplaza el dominio del CMS por el del bucket en una URL individual.
 */
export function fixWordPressUrl(url: string | undefined | null): string {
  if (!url) return '';
  
  let cmsHostname = 'cms.derechoune.edu.py';
  try {
    cmsHostname = new URL(wpUrl).hostname;
  } catch (e) {}

  const regex = new RegExp(`(https?:\/\/)${cmsHostname}`, 'g');
  return url.replace(regex, bucketUrl);
}

/**
 * Reemplaza las URLs de imágenes que apuntan al CMS por URLs del bucket de medios en un bloque de HTML.
 */
export function fixWordPressImages(html: string): string {
  if (!html) return '';

  let cmsHostname = 'cms.derechoune.edu.py';
  try {
    cmsHostname = new URL(wpUrl).hostname;
  } catch (e) {}
  
  const regex = new RegExp(`(https?:\/\/)${cmsHostname}`, 'g');
  return html.replace(regex, bucketUrl);
}
