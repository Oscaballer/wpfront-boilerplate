<?php
/**
 * Plugin Name: MiniCommerce
 * Description: Catálogo de productos headless para Eros. Admin, GCP Storage y WPGraphQL.
 * Version: 0.1.0
 * Author: Oscar Caballero
 * Text Domain: minicommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MC_VERSION', '0.1.0' );
define( 'MC_PLUGIN_FILE', __FILE__ );
define( 'MC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MC_GCP_PREFIX', 'minicommerce/catalogo/' );
define( 'MC_DEFAULT_GCP_HOST', 'https://media.eros.com.py' );

require_once MC_PLUGIN_DIR . 'includes/class-db.php';
require_once MC_PLUGIN_DIR . 'includes/class-gcp-storage.php';
require_once MC_PLUGIN_DIR . 'includes/class-revalidate.php';
require_once MC_PLUGIN_DIR . 'includes/class-migrator.php';
require_once MC_PLUGIN_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, array( 'MiniCommerce_DB', 'install' ) );
register_deactivation_hook( __FILE__, array( 'MiniCommerce_DB', 'deactivate' ) );

/**
 * @return MiniCommerce_GCP_Storage
 */
function mc_gcp() {
    static $instance = null;
    if ( null === $instance ) {
        $instance = new MiniCommerce_GCP_Storage();
    }
    return $instance;
}

/**
 * URL pública de una imagen del catálogo.
 */
function mc_image_url( $filename ) {
    return mc_gcp()->get_public_url( $filename );
}

/**
 * novedad == 2 significa producto destacado/novedad en el legacy.
 */
function mc_is_new( $novedad ) {
    return (int) $novedad === 2;
}

add_action( 'plugins_loaded', 'minicommerce_init' );

function minicommerce_init() {
    MiniCommerce_Admin::init();
    MiniCommerce_Revalidate::init();
}
