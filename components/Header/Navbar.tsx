'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { MenuItem } from '@/lib/utils/menu';
import styles from './Header.module.css';

interface NavbarProps {
  items: MenuItem[];
}

export default function Navbar({ items }: NavbarProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [openDropdowns, setOpenDropdowns] = useState<string[]>([]); // mobile accordion
  const [activeDropdown, setActiveDropdown] = useState<string | null>(null); // desktop hover
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth <= 1024);
    };
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Lock body scroll when menu is open
  useEffect(() => {
    if (isOpen && isMobile) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }, [isOpen, isMobile]);

  // Header scroll shadow logic
  useEffect(() => {
    const handleScroll = () => {
      const header = document.getElementById('header');
      if (window.scrollY > 20) {
        header?.classList.add(styles.scrolled);
      } else {
        header?.classList.remove(styles.scrolled);
      }
    };
    handleScroll(); // Initial check
    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const toggleMenu = () => setIsOpen(!isOpen);
  const closeMenu = () => {
    setIsOpen(false);
    setOpenDropdowns([]);
  };

  // Mobile: toggle accordion
  const toggleDropdown = (id: string, e: React.MouseEvent) => {
    if (isMobile) {
      e.preventDefault();
      setOpenDropdowns(prev =>
        prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
      );
    }
  };

  // Desktop: mostrar dropdown al hacer hover
  const handleMouseEnter = (id: string) => {
    if (!isMobile) setActiveDropdown(id);
  };

  // Desktop: ocultar dropdown al salir
  const handleMouseLeave = () => {
    if (!isMobile) setActiveDropdown(null);
  };

  // Desktop: cerrar dropdown al hacer clic en link hijo
  const handleChildLinkClick = () => {
    if (!isMobile) {
      setActiveDropdown(null);
    } else {
      closeMenu();
    }
  };

  const renderMenuItems = (menuItems: MenuItem[], isSubmenu = false, parentId?: string) => {
    if (!menuItems || menuItems.length === 0) return null;

    return (
      <ul className={isSubmenu ? styles.navDropdown : styles.navList}>
        {menuItems.map((item) => {
          const hasChildren = item.children && item.children.length > 0;
          const linkId = item.label === 'Oferta Académica' ? 'nav-oferta' : undefined;
          const isDropdownOpen = openDropdowns.includes(item.id);
          const isActive = activeDropdown === item.id;

          return (
            <li
              key={item.id}
              className={[
                hasChildren ? styles.hasDropdown : '',
                isDropdownOpen ? styles.dropdownOpen : '',
                isActive ? styles.dropdownActive : '',
              ].join(' ').trim()}
              onMouseEnter={hasChildren && !isMobile && !isSubmenu ? () => handleMouseEnter(item.id) : undefined}
              onMouseLeave={hasChildren && !isMobile && !isSubmenu ? handleMouseLeave : undefined}
            >
              <Link
                href={item.path || "#"}
                id={linkId}
                onClick={
                  hasChildren
                    ? (e) => toggleDropdown(item.id, e)
                    : () => handleChildLinkClick()
                }
              >
                {item.label}
              </Link>
              {hasChildren && renderMenuItems(item.children || [], true, item.id)}
            </li>
          );
        })}
      </ul>
    );
  };

  return (
    <nav className={`${styles.nav} ${isOpen ? styles.navActive : ''}`} id="main-nav">
      <div className={styles.navContainer}>
        {renderMenuItems(items)}

        <button
          className={`${styles.hamburger} ${isOpen ? styles.active : ''}`}
          id="hamburger"
          aria-label="Menú de navegación"
          onClick={toggleMenu}
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>

      {/* Overlay para cerrar el menú en móvil */}
      <div
        className={`${styles.navOverlay} ${isOpen ? styles.active : ''}`}
        onClick={closeMenu}
      ></div>
    </nav>
  );
}
