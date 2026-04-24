/**
 * i18n.config.ts
 * 
 * Configuración de internacionalización basada en variables de entorno.
 */

export const i18nConfig = {
  // Interruptor principal para activar/desactivar el soporte multilenguaje
  enabled: process.env.NEXT_PUBLIC_ENABLE_I18N === 'true',

  // Idioma por defecto
  defaultLocale: process.env.NEXT_PUBLIC_DEFAULT_LOCALE || 'es',

  // Idiomas soportados por el sitio (se convierte de string separado por comas a array)
  locales: (process.env.NEXT_PUBLIC_SUPPORTED_LOCALES || 'es,en,pt').split(','),
} as const;

export type Locale = typeof i18nConfig.locales[number];
