import { headers } from "next/headers";
import { i18nConfig, Locale } from "../../i18n.config";

export async function getLocale(): Promise<Locale> {
  if (!i18nConfig.enabled) {
    return i18nConfig.defaultLocale;
  }

  const headersList = await headers();
  const localeHeader = headersList.get("x-locale");

  if (localeHeader && i18nConfig.locales.includes(localeHeader as Locale)) {
    return localeHeader as Locale;
  }

  return i18nConfig.defaultLocale;
}
