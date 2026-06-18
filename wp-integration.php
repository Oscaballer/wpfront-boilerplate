<?php
/**
 * Plugin Name: WPFront - Sincronización de Estilos del Frontend
 * Description: Integra los estilos visuales del frontend (colores, tipografía y diseño) en el editor de bloques (Gutenberg) de WordPress.
 * Version: 1.0.0
 * Author: WPFront Team
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola los estilos del frontend para el editor de bloques.
 */
function wpfront_enqueue_editor_styles() {
    // 1. Cargar Google Fonts
    wp_enqueue_style(
        'wpfront-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap',
        array(),
        null
    );

    // 2. Definir los estilos personalizados (Design Tokens + Content Styles)
    $custom_css = "
        /* Design Tokens */
        :root {
            --color-primary: #2563eb;
            --color-primary-dark: #1d4ed8;
            --color-primary-light: #60a5fa;
            --color-accent: #f59e0b;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-bg: #f9fafb;
            --color-bg-white: #ffffff;
            --color-bg-alt: #f3f4f6;
            --color-border: #e5e7eb;
            --font-heading: 'Inter', system-ui, -apple-system, sans-serif;
            --font-body: 'Roboto', system-ui, -apple-system, sans-serif;
            --space-md: 1rem;
            --space-lg: 1.75rem;
            --space-xl: 2.5rem;
            --space-2xl: 3.5rem;
            --radius-sm: 4px;
            --radius-md: 8px;
        }

        /* Ajustes para el contenedor del editor */
        .editor-styles-wrapper {
            background-color: var(--color-bg) !important;
            color: var(--color-text) !important;
            font-family: var(--font-body) !important;
            line-height: 1.8 !important;
            padding-top: 40px !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
        }

        /* Forzar que los bloques individuales ocupen el ancho del contenedor */
        .editor-styles-wrapper .wp-block {
            max-width: none !important;
        }

        /* Tipografía de encabezados */
        .editor-styles-wrapper h1,
        .editor-styles-wrapper h2,
        .editor-styles-wrapper h3,
        .editor-styles-wrapper h4,
        .editor-styles-wrapper h5,
        .editor-styles-wrapper h6 {
            font-family: var(--font-heading) !important;
            color: var(--color-primary-dark) !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            margin-top: var(--space-2xl) !important;
            margin-bottom: var(--space-md) !important;
        }

        .editor-styles-wrapper h1 { font-size: 2rem !important; }
        .editor-styles-wrapper h2 { font-size: 1.8rem !important; border-bottom: 2px solid var(--color-primary-light); padding-bottom: 0.5rem; }
        .editor-styles-wrapper h3 { font-size: 1.5rem !important; }

        /* Párrafos y texto */
        .editor-styles-wrapper p {
            font-size: 1.05rem !important;
            margin-bottom: var(--space-lg) !important;
            text-align: justify !important;
        }
        .editor-styles-wrapper h1 {
            padding-left: var(--space-md);
            border-left: 3px solid var(--color-accent);
        }   

        /* Enlaces */
        .editor-styles-wrapper a {
            color: var(--color-primary) !important;
            text-decoration: underline !important;
            font-weight: 600 !important;
        }

        /* Listas */
        .editor-styles-wrapper ul,
        .editor-styles-wrapper ol {
            margin-bottom: var(--space-lg) !important;
            padding-left: var(--space-xl) !important;
        }

        .editor-styles-wrapper li {
            margin-bottom: 0.5rem !important;
        }

        /* Citas (Blockquote) */
        .editor-styles-wrapper blockquote {
            border-left: 4px solid var(--color-accent) !important;
            background: var(--color-bg-alt) !important;
            padding: var(--space-lg) var(--space-xl) !important;
            font-style: italic !important;
            font-size: 1.1rem !important;
            color: var(--color-text-light) !important;
            margin: var(--space-xl) 0 !important;
        }

        /* Imágenes */
        .editor-styles-wrapper img {
            border-radius: var(--radius-md) !important;
            box-shadow: 0 4px 16px rgba(30, 10, 15, 0.12) !important;
        }

        /* Tablas */
        .editor-styles-wrapper table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: var(--space-xl) 0 !important;
            font-size: 0.9rem !important;
            background: var(--color-bg-white) !important;
        }

        .editor-styles-wrapper th,
        .editor-styles-wrapper td {
            padding: 0.75rem 1rem !important;
            border: 1px solid var(--color-border) !important;
        }

        .editor-styles-wrapper th {
            background-color: var(--color-bg-alt) !important;
            font-weight: 600 !important;
        }
    ";

    wp_add_inline_style( 'wp-block-library', $custom_css );
}
add_action( 'enqueue_block_editor_assets', 'wpfront_enqueue_editor_styles' );

