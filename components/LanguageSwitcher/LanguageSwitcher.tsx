"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { i18nConfig, Locale } from "@/i18n.config";
import styles from "./LanguageSwitcher.module.css";

interface Props {
  currentLocale: Locale;
}

export default function LanguageSwitcher({ currentLocale }: Props) {
  const pathname = usePathname();

  if (!i18nConfig.enabled) {
    return null;
  }

  const getTargetUrl = (locale: Locale) => {
    // pathname viene sin el prefijo del locale gracias al rewrite del middleware,
    // excepto si estamos en el cliente y Next.js no ha hidratado correctamente el rewrite,
    // pero el App Router maneja esto bien.
    // Si locale es el default, no ponemos prefijo
    if (locale === i18nConfig.defaultLocale) {
      return pathname === "/" ? "/" : pathname;
    }
    // Si no es el default, añadimos el prefijo
    return `/${locale}${pathname === "/" ? "" : pathname}`;
  };

  return (
    <div className={styles.switcher}>
      {i18nConfig.locales.map((locale) => (
        <Link
          key={locale}
          href={getTargetUrl(locale)}
          className={`${styles.link} ${
            currentLocale === locale ? styles.active : ""
          }`}
        >
          {locale}
        </Link>
      ))}
    </div>
  );
}
