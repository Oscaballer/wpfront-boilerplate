import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { i18nConfig } from './i18n.config';

export function proxy(request: NextRequest) {
  // 1. Si i18n está desactivado, pasamos la request tal cual.
  if (!i18nConfig.enabled) {
    return NextResponse.next();
  }

  const { pathname } = request.nextUrl;

  // Ignorar peticiones a archivos estáticos y API
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/api') ||
    pathname.includes('.')
  ) {
    return NextResponse.next();
  }

  // Comprobar si el pathname empieza con alguno de los locales (ej. /en/...)
  const pathnameHasLocale = i18nConfig.locales.some(
    (locale) => pathname.startsWith(`/${locale}/`) || pathname === `/${locale}`
  );

  if (pathnameHasLocale) {
    // Extraer el locale de la URL
    const locale = pathname.split('/')[1];
    
    // Crear una nueva URL removiendo el prefijo del idioma para reescritura interna
    // Ej: /en/noticias -> /noticias
    const newPathname = pathname.replace(`/${locale}`, '') || '/';
    const rewriteUrl = new URL(newPathname, request.url);

    // Reescribimos la petición e inyectamos el locale real en el header
    const response = NextResponse.rewrite(rewriteUrl);
    response.headers.set('x-locale', locale);
    
    return response;
  }

  // Si no tiene locale en la URL, significa que es el defaultLocale.
  // Inyectamos el defaultLocale en el header para que el backend sepa.
  const response = NextResponse.next();
  response.headers.set('x-locale', i18nConfig.defaultLocale);
  return response;
}

export const config = {
  // Aplicar a todas las rutas excepto a /_next, /api y archivos con extensión
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico|.*\\.).*)'],
};
