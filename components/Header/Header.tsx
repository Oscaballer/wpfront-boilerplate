import Link from "next/link";
import { graphqlClient } from "@/lib/graphql/client";
import { GET_MENU_ITEMS } from "@/lib/graphql/queries/menu";
import { buildMenuTree, MenuItem } from "@/lib/utils/menu";
import { getLocale } from "@/lib/utils/i18n";
import { Locale } from "@/i18n.config";
import LanguageSwitcher from "../LanguageSwitcher/LanguageSwitcher";
import styles from "./Header.module.css";

interface MenuData {
  menuItems: {
    nodes: MenuItem[];
  };
}

async function getMenu(locale: Locale) {
  try {
    const langParam = locale.toUpperCase(); // WPGraphQL suele usar mayúsculas ES, EN
    const data = await graphqlClient.request<MenuData>(GET_MENU_ITEMS, { language: langParam });
    return buildMenuTree(data.menuItems.nodes);
  } catch (error) {
    console.error("Error fetching menu:", error);
    return [];
  }
}

function MenuTree({ items, isSubmenu = false }: { items: MenuItem[]; isSubmenu?: boolean }) {
  if (!items || items.length === 0) return null;

  return (
    <ul className={isSubmenu ? styles.subMenu : styles.menu}>
      {items.map((item) => (
        <li key={item.id} className={isSubmenu ? styles.subMenuItem : styles.menuItem}>
          <Link 
            href={item.path || "#"} 
            className={isSubmenu ? styles.subMenuLink : styles.menuLink}
          >
            {item.label}
          </Link>
          {item.children && item.children.length > 0 && (
            <MenuTree items={item.children} isSubmenu={true} />
          )}
        </li>
      ))}
    </ul>
  );
}

export default async function Header() {
  const locale = await getLocale();
  const menuItems = await getMenu(locale);

  return (
    <header className={styles.header}>
      <div className={styles.container}>
        <Link href="/" className={styles.logo}>
          WebUne
        </Link>
        <nav className={styles.nav}>
          <MenuTree items={menuItems} />
          <LanguageSwitcher currentLocale={locale} />
        </nav>
      </div>
    </header>
  );
}
