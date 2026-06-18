import Link from "next/link";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_MENU_ITEMS } from "@/lib/graphql/queries/menu";
import { buildMenuTree, MenuItem } from "@/lib/utils/menu";
import { getLocale } from "@/lib/utils/i18n";
import styles from "./Header.module.css";
import Image from "next/image";
import Navbar from "./Navbar";
import SearchModal from "../AlgoliaSearch/SearchModal";

interface MenuData {
  menuItems: {
    nodes: MenuItem[];
  };
}

async function getMenu() {
  try {
    const data = await graphqlClient.request<MenuData>(GET_MENU_ITEMS, undefined, { tags: ['menu'] });
    return buildMenuTree(data.menuItems.nodes);
  } catch (error) {
    console.error("Error fetching menu:", error);
    return [];
  }
}

export default async function Header() {
  const menuItems = await getMenu();
  const siteName = process.env.NEXT_PUBLIC_SITE_NAME || "Next.js WP Boilerplate";
  const siteSubName = process.env.NEXT_PUBLIC_SITE_SUB_NAME || "Headless WordPress Starter";

  return (
    <header className={styles.header} id="header">
      {/* Top Bar */}
      <div className={styles.headerTop}>
        <div className={styles.headerTopContainer}>
          {/* Brand */}
          <Link href="/" className={styles.headerBrand} id="brand-link">
            {process.env.NEXT_PUBLIC_SITE_LOGO ? (
              <Image src={process.env.NEXT_PUBLIC_SITE_LOGO} alt={siteName} width={86} height={86} className={styles.headerLogo} />
            ) : (
              <div className={styles.headerLogoPlaceholder}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" width={40} height={40}>
                  <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                </svg>
              </div>
            )}
            <div className={styles.headerBrandText}>
              <h1>{siteName}</h1>
              {siteSubName && <p>{siteSubName}</p>}
            </div>
          </Link>

          {/* Actions */}
          <div className={styles.headerActions}>
            {/* Contact */}
            {process.env.NEXT_PUBLIC_CONTACT_PHONE && (
              <div className={styles.headerContact}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
                <a href={`tel:${process.env.NEXT_PUBLIC_CONTACT_PHONE}`} id="header-phone">
                  {process.env.NEXT_PUBLIC_CONTACT_PHONE_LABEL || process.env.NEXT_PUBLIC_CONTACT_PHONE}
                </a>
              </div>
            )}

            {/* Social */}
            {(process.env.NEXT_PUBLIC_SOCIAL_FACEBOOK || process.env.NEXT_PUBLIC_SOCIAL_INSTAGRAM) && (
              <div className={styles.headerSocial}>
                {process.env.NEXT_PUBLIC_SOCIAL_FACEBOOK && (
                  <a href={process.env.NEXT_PUBLIC_SOCIAL_FACEBOOK} aria-label="Facebook" id="social-fb" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                    </svg>
                  </a>
                )}
                {process.env.NEXT_PUBLIC_SOCIAL_INSTAGRAM && (
                  <a href={process.env.NEXT_PUBLIC_SOCIAL_INSTAGRAM} aria-label="Instagram" id="social-ig" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                    </svg>
                  </a>
                )}
              </div>
            )}

            {/* Search */}
            <div className={styles.headerSearch} id="header-search">
              <SearchModal />
            </div>
          </div>
        </div>
      </div>

      <Navbar items={menuItems} />
    </header>
  );
}
