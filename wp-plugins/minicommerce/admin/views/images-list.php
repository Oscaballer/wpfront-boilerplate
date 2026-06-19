<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table      = MiniCommerce_DB::table( 'imagen_articulo' );
$prod_table = MiniCommerce_DB::table( 'articulo' );
$product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;

$product = null;
if ( $product_id ) {
    $product = $wpdb->get_row( $wpdb->prepare( "SELECT id, nombre, codigo FROM {$prod_table} WHERE id = %d", $product_id ) );
}

$per_page = 50;
$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$where   = '1=1';
$wpdb_args = array();
if ( $product_id ) {
    $where       = 'id_articulo = %d';
    $wpdb_args[] = $product_id;
}

$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $wpdb_args ) );
$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge( $wpdb_args, array( $per_page, $offset ) )
    )
);
$total_pages = ceil( $total / $per_page );
?>
<div class="wrap mc-wrap">
    <h1 class="wp-heading-inline">Imágenes adicionales</h1>
    <?php if ( $product ) : ?>
        <p>Artículo: <strong><?php echo esc_html( $product->nombre ); ?></strong> (<?php echo esc_html( $product->codigo ?? 'ID: ' . $product->id ); ?>)</p>
    <?php endif; ?>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-images&action=new' . ( $product_id ? '&product_id=' . $product_id : '' ) ) ); ?>" class="page-title-action">Añadir imagen</a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-products' ) ); ?>" class="page-title-action" style="margin-left:8px">&larr; Volver a artículos</a>

    <?php settings_errors( 'mc_messages' ); ?>

    <?php if ( ! $product_id ) : ?>
        <form method="get">
            <input type="hidden" name="page" value="minicommerce-images" />
            <p>
                <label>Filtrar por artículo ID: <input type="number" name="product_id" min="1" value="" /></label>
                <input type="submit" class="button" value="Filtrar" />
            </p>
        </form>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" width="50">ID</th>
                <th scope="col" width="80">Imagen</th>
                <th scope="col">Artículo ID</th>
                <th scope="col">Descripción</th>
                <th scope="col" width="140">Fecha</th>
                <th scope="col" width="80">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $items ) ) : ?>
                <tr><td colspan="6">No hay imágenes adicionales.</td></tr>
            <?php endif; ?>
            <?php foreach ( $items as $img ) : ?>
                <tr>
                    <td><?php echo esc_html( $img->id ); ?></td>
                    <td>
                        <img src="<?php echo esc_url( mc_image_url( $img->imagen ) ); ?>"
                             alt="" style="width:60px;height:60px;object-fit:cover;border-radius:2px;" />
                    </td>
                    <td><?php echo esc_html( $img->id_articulo ); ?></td>
                    <td><?php echo esc_html( mb_substr( (string) $img->descripcion, 0, 100 ) ); ?></td>
                    <td><?php echo esc_html( $img->created_at ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=minicommerce-images&action=delete&id=' . (int) $img->id . ( $product_id ? '&product_id=' . $product_id : '' ) ), 'mc_delete_image_' . $img->id ) ); ?>"
                           onclick="return confirm('¿Eliminar esta imagen?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html( $total ); ?> elementos</span>
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $paged,
                ) );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>