<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiniCommerce_Admin {

    const MENU_SLUG = 'minicommerce';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_mc_upload_images_batch', array( __CLASS__, 'ajax_upload_images_batch' ) );
        add_action( 'wp_ajax_mc_get_subcats', array( __CLASS__, 'ajax_get_subcats' ) );
    }

    public static function register_menus() {
        add_menu_page(
            'MiniCommerce',
            'MiniCommerce',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_dashboard' ),
            'dashicons-cart',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Dashboard',
            'Dashboard',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_dashboard' )
        );

        $items = array(
            'products'  => array( 'Artículos', array( __CLASS__, 'render_products' ) ),
            'sections'  => array( 'Secciones', array( __CLASS__, 'render_sections' ) ),
            'subcats'   => array( 'Subcategorías', array( __CLASS__, 'render_subcats' ) ),
            'images'    => array( 'Imágenes', array( __CLASS__, 'render_images' ) ),
            'migrator'  => array( 'Migrador', array( __CLASS__, 'render_migrator' ) ),
            'settings'  => array( 'Ajustes', array( __CLASS__, 'render_settings_general' ) ),
            'gcp'       => array( 'Ajustes GCP', array( __CLASS__, 'render_settings_gcp' ) ),
        );

        foreach ( $items as $slug => $item ) {
            add_submenu_page(
                self::MENU_SLUG,
                $item[0],
                $item[0],
                'manage_options',
                self::MENU_SLUG . '-' . $slug,
                $item[1]
            );
        }
    }

    public static function register_settings() {
        register_setting( 'mc_general_options', 'mc_whatsapp_number', array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_whatsapp' ),
        ) );
        register_setting( 'mc_general_options', MiniCommerce_Revalidate::OPTION_URL, array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        register_setting( 'mc_general_options', MiniCommerce_Revalidate::OPTION_SECRET, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'mc_gcp_options', MiniCommerce_GCP_Storage::OPTION_BUCKET, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'mc_gcp_options', MiniCommerce_GCP_Storage::OPTION_HOST, array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        register_setting( 'mc_gcp_options', MiniCommerce_GCP_Storage::OPTION_JSON, array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_gcp_json' ),
        ) );

        register_setting( 'mc_migration_options', MiniCommerce_Migrator::OPTION_IMAGE_PATH, array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
    }

    public static function sanitize_whatsapp( $value ) {
        return preg_replace( '/[^0-9+]/', '', (string) $value );
    }

    public static function sanitize_gcp_json( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }
        $decoded = json_decode( $value, true );
        if ( ! is_array( $decoded ) ) {
            add_settings_error( 'mc_gcp_options', 'mc_gcp_json_invalid', 'El JSON de la cuenta de servicio no es válido.', 'error' );
            return get_option( MiniCommerce_GCP_Storage::OPTION_JSON, '' );
        }
        return $value;
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) {
            return;
        }
        wp_enqueue_style( 'mc-admin', MC_PLUGIN_URL . 'admin/css/admin.css', array(), MC_VERSION );
        wp_enqueue_script( 'mc-admin', MC_PLUGIN_URL . 'admin/js/admin.js', array(), MC_VERSION, true );
        wp_localize_script( 'mc-admin', 'mcAdminNonce', wp_create_nonce( 'mc_admin_ajax' ) );
    }

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_POST['mc_action'] ) && empty( $_GET['mc_action'] ) ) {
            return;
        }

        $action = '';
        if ( ! empty( $_POST['mc_action'] ) ) {
            check_admin_referer( 'mc_admin_action' );
            $action = sanitize_text_field( wp_unslash( $_POST['mc_action'] ) );
        }

        if ( 'delete' === ( $_GET['mc_action'] ?? '' ) && ! empty( $_GET['id'] ) && ! empty( $_GET['_wpnonce'] ) && ! empty( $_GET['page'] ) ) {
            $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
            $id   = (int) $_GET['id'];

            if ( 'minicommerce-sections' === $page ) {
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'mc_delete_section_' . $id ) ) {
                    self::delete_section( $id );
                }
            } elseif ( 'minicommerce-subcats' === $page ) {
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'mc_delete_subcat_' . $id ) ) {
                    self::delete_subcat( $id );
                }
            } elseif ( 'minicommerce-products' === $page ) {
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'mc_delete_product_' . $id ) ) {
                    self::delete_product( $id );
                }
            } elseif ( 'minicommerce-images' === $page ) {
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'mc_delete_image_' . $id ) ) {
                    self::delete_image( $id );
                }
            }
            return;
        }

        if ( 'import_sql' === $action && ! empty( $_FILES['mc_sql_file']['tmp_name'] ) ) {
            $tmp  = $_FILES['mc_sql_file']['tmp_name'];
            $dest = wp_upload_dir()['basedir'] . '/minicommerce-migration/eros_backup.sql';
            wp_mkdir_p( dirname( $dest ) );

            if ( ! move_uploaded_file( $tmp, $dest ) ) {
                add_settings_error( 'mc_messages', 'mc_sql_move', 'No se pudo guardar el archivo SQL.', 'error' );
            } else {
                $result = MiniCommerce_Migrator::import_sql_file( $dest );
                add_settings_error(
                    'mc_messages',
                    'mc_sql_import',
                    $result['message'],
                    $result['success'] ? 'updated' : 'error'
                );
            }
        }

        if ( 'test_gcp_upload' === $action && ! empty( $_FILES['mc_test_image']['tmp_name'] ) ) {
            $result = mc_gcp()->upload_from_request( $_FILES['mc_test_image'] );
            if ( is_wp_error( $result ) ) {
                add_settings_error( 'mc_messages', 'mc_gcp_test', $result->get_error_message(), 'error' );
            } else {
                $filename = sanitize_file_name( $_FILES['mc_test_image']['name'] );
                add_settings_error(
                    'mc_messages',
                    'mc_gcp_test',
                    'Imagen subida: ' . esc_html( mc_gcp()->get_public_url( $filename ) ),
                    'updated'
                );
            }
        }

        if ( 'test_revalidate' === $action ) {
            $results = MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
            if ( $results && ! empty( $results['minicommerce'] ) ) {
                add_settings_error( 'mc_messages', 'mc_revalidate_ok', 'Revalidación enviada correctamente.', 'updated' );
            } else {
                add_settings_error( 'mc_messages', 'mc_revalidate_fail', 'No se pudo revalidar. Revisa URL y secret.', 'error' );
            }
        }

        if ( 'save_section' === $action ) {
            self::save_section();
        }

        if ( 'save_subcat' === $action ) {
            self::save_subcat();
        }

        if ( 'save_product' === $action ) {
            self::save_product();
        }

        if ( 'save_image' === $action ) {
            self::save_image();
        }
    }

    private static function save_section() {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'seccion' );

        $id          = ! empty( $_POST['section_id'] ) ? (int) $_POST['section_id'] : 0;
        $nombre      = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
        $descripcion = sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ?? '' ) );
        $publicado   = ! empty( $_POST['publicado'] ) ? 1 : 0;
        $orden       = isset( $_POST['orden'] ) ? (int) $_POST['orden'] : 0;

        if ( empty( $nombre ) ) {
            add_settings_error( 'mc_messages', 'mc_section_name', 'El nombre es obligatorio.', 'error' );
            return;
        }

        $data = array(
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'publicado'   => $publicado,
            'orden'       => $orden,
            'slug'        => self::slugify( $nombre ),
        );
        $fmt  = array( '%s', '%s', '%d', '%d', '%s' );

        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ), $fmt, array( '%d' ) );
            $msg = 'Sección actualizada.';
        } else {
            $wpdb->insert( $table, $data, $fmt );
            $msg = 'Sección creada.';
        }

        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_section_saved', $msg, 'updated' );
    }

    private static function delete_section( $id ) {
        global $wpdb;
        $wpdb->delete( MiniCommerce_DB::table( 'seccion' ), array( 'id' => $id ), array( '%d' ) );
        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_section_deleted', 'Sección eliminada.', 'updated' );
    }

    private static function save_subcat() {
        global $wpdb;
        $table     = MiniCommerce_DB::table( 'sub_categoria' );

        $id        = ! empty( $_POST['subcat_id'] ) ? (int) $_POST['subcat_id'] : 0;
        $nombre    = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
        $id_seccion = ! empty( $_POST['id_seccion'] ) ? (int) $_POST['id_seccion'] : 0;

        if ( empty( $nombre ) || ! $id_seccion ) {
            add_settings_error( 'mc_messages', 'mc_subcat_fields', 'El nombre y la sección son obligatorios.', 'error' );
            return;
        }

        $data = array(
            'nombre'     => $nombre,
            'id_seccion' => $id_seccion,
            'slug'       => self::slugify( $nombre ),
        );
        $fmt  = array( '%s', '%d', '%s' );

        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ), $fmt, array( '%d' ) );
            $msg = 'Subcategoría actualizada.';
        } else {
            $wpdb->insert( $table, $data, $fmt );
            $msg = 'Subcategoría creada.';
        }

        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_subcat_saved', $msg, 'updated' );
    }

    private static function delete_subcat( $id ) {
        global $wpdb;
        $wpdb->delete( MiniCommerce_DB::table( 'sub_categoria' ), array( 'id' => $id ), array( '%d' ) );
        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_subcat_deleted', 'Subcategoría eliminada.', 'updated' );
    }

    private static function save_product() {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'articulo' );

        $id             = ! empty( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
        $nombre         = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
        $codigo         = sanitize_text_field( wp_unslash( $_POST['codigo'] ?? '' ) );
        $descripcion    = sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ?? '' ) );
        $precio         = sanitize_text_field( wp_unslash( $_POST['precio'] ?? '' ) );
        $id_seccion     = ! empty( $_POST['id_seccion'] ) ? (int) $_POST['id_seccion'] : 0;
        $id_subcategoria = ! empty( $_POST['id_sub_categoria'] ) ? (int) $_POST['id_sub_categoria'] : null;
        $novedad        = isset( $_POST['novedad'] ) ? (int) $_POST['novedad'] : 0;
        $orden          = isset( $_POST['orden'] ) ? (int) $_POST['orden'] : 0;
        $publicado      = ! empty( $_POST['publicado'] ) ? 1 : 0;

        if ( empty( $nombre ) ) {
            add_settings_error( 'mc_messages', 'mc_product_name', 'El nombre es obligatorio.', 'error' );
            return;
        }

        if ( $id ) {
            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT imagen FROM {$table} WHERE id = %d", $id ) );
        } else {
            $existing = null;
        }

        $imagen = $existing;
        if ( ! empty( $_POST['eliminar_imagen'] ) && $existing ) {
            mc_gcp()->delete( $existing );
            $imagen = '';
        }

        if ( ! empty( $_FILES['imagen']['tmp_name'] ) && is_uploaded_file( $_FILES['imagen']['tmp_name'] ) ) {
            $gcp_result = mc_gcp()->upload_from_request( $_FILES['imagen'] );
            if ( is_wp_error( $gcp_result ) ) {
                add_settings_error( 'mc_messages', 'mc_product_image', 'Error al subir imagen: ' . $gcp_result->get_error_message(), 'error' );
            } else {
                $imagen = sanitize_file_name( $_FILES['imagen']['name'] );
            }
        }

        $data = array(
            'nombre'           => $nombre,
            'codigo'           => $codigo ?: null,
            'descripcion'      => $descripcion ?: null,
            'precio'           => $precio ?: null,
            'imagen'           => $imagen ?: null,
            'id_seccion'       => $id_seccion,
            'id_sub_categoria' => $id_subcategoria ?: null,
            'novedad'          => $novedad,
            'orden'            => $orden,
            'publicado'        => $publicado,
        );
        $fmt = array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d' );

        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ), $fmt, array( '%d' ) );
            $msg = 'Artículo actualizado.';
        } else {
            $wpdb->insert( $table, $data, $fmt );
            $msg = 'Artículo creado.';
        }

        MiniCommerce_Revalidate::trigger( array( 'minicommerce', 'minicommerce-products' ) );
        add_settings_error( 'mc_messages', 'mc_product_saved', $msg, 'updated' );
    }

    private static function delete_product( $id ) {
        global $wpdb;
        $img_table = MiniCommerce_DB::table( 'imagen_articulo' );
        $art_table = MiniCommerce_DB::table( 'articulo' );

        $product = $wpdb->get_row( $wpdb->prepare( "SELECT imagen FROM {$art_table} WHERE id = %d", $id ) );
        if ( $product && $product->imagen ) {
            mc_gcp()->delete( $product->imagen );
        }

        $images = $wpdb->get_col( $wpdb->prepare( "SELECT imagen FROM {$img_table} WHERE id_articulo = %d", $id ) );
        foreach ( $images as $img ) {
            if ( $img ) {
                mc_gcp()->delete( $img );
            }
        }

        $wpdb->delete( $img_table, array( 'id_articulo' => $id ), array( '%d' ) );
        $wpdb->delete( $art_table, array( 'id' => $id ), array( '%d' ) );

        MiniCommerce_Revalidate::trigger( array( 'minicommerce', 'minicommerce-products' ) );
        add_settings_error( 'mc_messages', 'mc_product_deleted', 'Artículo eliminado.', 'updated' );
    }

    private static function save_image() {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'imagen_articulo' );

        $id         = ! empty( $_POST['image_id'] ) ? (int) $_POST['image_id'] : 0;
        $id_articulo = ! empty( $_POST['id_articulo'] ) ? (int) $_POST['id_articulo'] : 0;
        $descripcion = sanitize_text_field( wp_unslash( $_POST['descripcion'] ?? '' ) );

        if ( ! $id_articulo ) {
            add_settings_error( 'mc_messages', 'mc_image_product', 'El artículo es obligatorio.', 'error' );
            return;
        }

        $imagen = null;
        if ( $id ) {
            $imagen = $wpdb->get_var( $wpdb->prepare( "SELECT imagen FROM {$table} WHERE id = %d", $id ) );
        }

        if ( ! empty( $_FILES['imagen']['tmp_name'] ) && is_uploaded_file( $_FILES['imagen']['tmp_name'] ) ) {
            $gcp_result = mc_gcp()->upload_from_request( $_FILES['imagen'] );
            if ( is_wp_error( $gcp_result ) ) {
                add_settings_error( 'mc_messages', 'mc_image_upload', 'Error al subir imagen: ' . $gcp_result->get_error_message(), 'error' );
                return;
            }
            $imagen = sanitize_file_name( $_FILES['imagen']['name'] );
        }

        if ( ! $imagen ) {
            add_settings_error( 'mc_messages', 'mc_image_file', 'Debes seleccionar un archivo de imagen.', 'error' );
            return;
        }

        $data = array(
            'id_articulo' => $id_articulo,
            'imagen'      => $imagen,
            'descripcion' => $descripcion ?: null,
        );
        $fmt  = array( '%d', '%s', '%s' );

        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ), $fmt, array( '%d' ) );
            $msg = 'Imagen actualizada.';
        } else {
            $wpdb->insert( $table, $data, $fmt );
            $msg = 'Imagen subida.';
        }

        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_image_saved', $msg, 'updated' );
    }

    private static function delete_image( $id ) {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'imagen_articulo' );

        $img = $wpdb->get_row( $wpdb->prepare( "SELECT imagen FROM {$table} WHERE id = %d", $id ) );
        if ( $img && $img->imagen ) {
            mc_gcp()->delete( $img->imagen );
        }

        $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
        MiniCommerce_Revalidate::trigger( array( 'minicommerce' ) );
        add_settings_error( 'mc_messages', 'mc_image_deleted', 'Imagen eliminada.', 'updated' );
    }

    public static function ajax_get_subcats() {
        check_ajax_referer( 'mc_admin_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
        }

        $seccion_id = isset( $_POST['seccion_id'] ) ? (int) $_POST['seccion_id'] : 0;
        if ( ! $seccion_id ) {
            wp_send_json_error( array( 'message' => 'Sección inválida.' ) );
        }

        global $wpdb;
        $table = MiniCommerce_DB::table( 'sub_categoria' );
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nombre FROM {$table} WHERE id_seccion = %d ORDER BY nombre",
                $seccion_id
            )
        );

        wp_send_json_success( $items );
    }

    public static function render_dashboard() {
        $counts = MiniCommerce_DB::counts();
        $gcp_ok = mc_gcp()->is_configured();
        include MC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function render_sections() {
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'new', 'edit' ), true ) ) {
            include MC_PLUGIN_DIR . 'admin/views/section-form.php';
        } else {
            include MC_PLUGIN_DIR . 'admin/views/sections-list.php';
        }
    }

    public static function render_subcats() {
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'new', 'edit' ), true ) ) {
            include MC_PLUGIN_DIR . 'admin/views/subcat-form.php';
        } else {
            include MC_PLUGIN_DIR . 'admin/views/subcats-list.php';
        }
    }

    public static function render_products() {
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'new', 'edit' ), true ) ) {
            include MC_PLUGIN_DIR . 'admin/views/product-form.php';
        } else {
            include MC_PLUGIN_DIR . 'admin/views/products-list.php';
        }
    }

    public static function render_images() {
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'new', 'edit' ), true ) ) {
            include MC_PLUGIN_DIR . 'admin/views/image-form.php';
        } else {
            include MC_PLUGIN_DIR . 'admin/views/images-list.php';
        }
    }

    public static function render_settings_general() {
        include MC_PLUGIN_DIR . 'admin/views/settings-general.php';
    }

    public static function render_settings_gcp() {
        include MC_PLUGIN_DIR . 'admin/views/settings-gcp.php';
    }

    public static function render_migrator() {
        include MC_PLUGIN_DIR . 'admin/views/migrator.php';
    }

    public static function ajax_upload_images_batch() {
        check_ajax_referer( 'mc_migration_ajax', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
        }

        $directory = get_option( MiniCommerce_Migrator::OPTION_IMAGE_PATH, '' );
        $offset    = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;

        $result = MiniCommerce_Migrator::upload_images_batch( $directory, $offset, 25 );

        if ( ! $result['success'] ) {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }

        wp_send_json_success( $result );
    }

    private static function slugify( $text ) {
        $text = remove_accents( $text );
        $text = strtolower( $text );
        $text = preg_replace( '/[^a-z0-9]+/', '-', $text );
        return trim( $text, '-' );
    }
}