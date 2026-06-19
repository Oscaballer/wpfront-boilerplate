<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiniCommerce_DB {

    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'mc_' . $name;
    }

    public static function install() {
        self::create_tables();
        update_option( 'mc_db_version', MC_VERSION );
    }

    public static function deactivate() {
        // Reservado: no eliminar tablas al desactivar.
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $articulo = self::table( 'articulo' );
        $seccion = self::table( 'seccion' );
        $subcat = self::table( 'sub_categoria' );
        $imagen = self::table( 'imagen_articulo' );

        $sql = "
        CREATE TABLE {$articulo} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL DEFAULT '',
            descripcion longtext NULL,
            precio varchar(20) NULL,
            imagen varchar(250) NULL,
            id_seccion bigint(20) unsigned NOT NULL DEFAULT 0,
            id_sub_categoria bigint(20) unsigned NULL DEFAULT NULL,
            novedad tinyint(1) NOT NULL DEFAULT 0,
            orden int(11) NOT NULL DEFAULT 0,
            codigo varchar(200) NULL,
            publicado tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_seccion (id_seccion),
            KEY idx_subcat (id_sub_categoria),
            KEY idx_novedad (novedad),
            KEY idx_codigo (codigo(50))
        ) {$charset};

        CREATE TABLE {$seccion} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL DEFAULT '',
            descripcion longtext NULL,
            publicado tinyint(1) NOT NULL DEFAULT 1,
            orden int(11) NOT NULL DEFAULT 0,
            slug varchar(255) NULL,
            PRIMARY KEY  (id),
            KEY idx_publicado (publicado),
            KEY idx_slug (slug(100))
        ) {$charset};

        CREATE TABLE {$subcat} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(250) NOT NULL DEFAULT '',
            id_seccion bigint(20) unsigned NOT NULL DEFAULT 0,
            slug varchar(255) NULL,
            PRIMARY KEY  (id),
            KEY idx_seccion (id_seccion),
            KEY idx_slug (slug(100))
        ) {$charset};

        CREATE TABLE {$imagen} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            id_articulo bigint(20) unsigned NOT NULL,
            imagen varchar(250) NOT NULL DEFAULT '',
            descripcion varchar(250) NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_articulo (id_articulo)
        ) {$charset};
        ";

        dbDelta( $sql );
    }

    /**
     * @return array<string, int>
     */
    public static function counts() {
        global $wpdb;

        return array(
            'articulos'      => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table( 'articulo' ) ),
            'secciones'      => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table( 'seccion' ) ),
            'subcategorias'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table( 'sub_categoria' ) ),
            'imagenes_extra' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table( 'imagen_articulo' ) ),
        );
    }
}
