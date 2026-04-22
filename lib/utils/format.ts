/**
 * lib/utils/format.ts
 *
 * Utilidades de formato comunes en proyectos con WordPress como CMS.
 */

// ─── Fechas ───────────────────────────────────────────────────────────────────

/**
 * Formatea una fecha ISO 8601 de WordPress a formato legible.
 *
 * @example
 * formatDate("2024-03-15T10:30:00Z")       // "15 de marzo de 2024"
 * formatDate("2024-03-15T10:30:00Z", "en") // "March 15, 2024"
 */
export function formatDate(
  isoDate: string,
  locale: string = "es",
  options: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "long",
    day: "numeric",
  }
): string {
  try {
    return new Date(isoDate).toLocaleDateString(locale, options);
  } catch {
    return isoDate;
  }
}

/**
 * Devuelve cuánto tiempo pasó desde la fecha en formato relativo.
 *
 * @example
 * timeAgo("2024-03-15T10:30:00Z") // "hace 3 días"
 */
export function timeAgo(isoDate: string, locale: string = "es"): string {
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: "auto" });
  const diff = new Date(isoDate).getTime() - Date.now();
  const seconds = Math.round(diff / 1000);
  const minutes = Math.round(seconds / 60);
  const hours = Math.round(minutes / 60);
  const days = Math.round(hours / 24);
  const weeks = Math.round(days / 7);
  const months = Math.round(days / 30);
  const years = Math.round(days / 365);

  if (Math.abs(seconds) < 60) return rtf.format(seconds, "second");
  if (Math.abs(minutes) < 60) return rtf.format(minutes, "minute");
  if (Math.abs(hours) < 24) return rtf.format(hours, "hour");
  if (Math.abs(days) < 7) return rtf.format(days, "day");
  if (Math.abs(weeks) < 5) return rtf.format(weeks, "week");
  if (Math.abs(months) < 12) return rtf.format(months, "month");
  return rtf.format(years, "year");
}

// ─── HTML ─────────────────────────────────────────────────────────────────────

/**
 * Extrae el texto plano de un string HTML.
 * Útil para generar meta descriptions o previews de contenido.
 *
 * @example
 * stripHtml("<p>Hola <strong>mundo</strong></p>") // "Hola mundo"
 */
export function stripHtml(html: string): string {
  return html
    .replace(/<[^>]*>/g, " ")   // reemplaza tags por espacio
    .replace(/\s+/g, " ")        // colapsa múltiples espacios
    .trim();
}

/**
 * Extrae el excerpt de un string HTML (primer párrafo con texto).
 * Fallback a stripHtml si no hay párrafos.
 */
export function getExcerptFromContent(html: string, maxLength = 160): string {
  const match = html.match(/<p[^>]*>([\s\S]*?)<\/p>/i);
  const text = match ? stripHtml(match[1]) : stripHtml(html);
  return truncate(text, maxLength);
}

// ─── Texto ────────────────────────────────────────────────────────────────────

/**
 * Trunca un texto a una longitud máxima, terminando en "…" si se corta.
 *
 * @example
 * truncate("Hola mundo, esto es largo", 10) // "Hola mundo…"
 */
export function truncate(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength).trimEnd() + "…";
}

/**
 * Convierte un slug de WordPress al título en formato Title Case.
 *
 * @example
 * slugToTitle("mi-primer-post") // "Mi Primer Post"
 */
export function slugToTitle(slug: string): string {
  return slug
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

// ─── URLs ─────────────────────────────────────────────────────────────────────

/**
 * Construye la URL canónica de un post en el frontend (no en WordPress).
 *
 * @example
 * getPostUrl("mi-primer-post") // "/blog/mi-primer-post"
 */
export function getPostUrl(slug: string): string {
  return `/blog/${slug}`;
}

/**
 * Construye la URL canónica de una página en el frontend.
 */
export function getPageUrl(slug: string): string {
  return `/${slug}`;
}
