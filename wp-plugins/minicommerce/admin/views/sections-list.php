<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table   = MiniCommerce_DB::table( 'seccion' );
$per_page = 20;
$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'orden';
$order   = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'DESC' ? 'DESC' : 'ASC';
$allowed_columns = array( 'id', 'nombre', 'publicado', 'orden' );
if ( ! in_array( $orderby, $allowed_columns, true ) ) {
    $orderby = 'orden';
}

$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
$total_pages = ceil( $total / $per_page );
?>
<div class="wrap mc-wrap">
    <h1 class="wp-heading-inline">Secciones</h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-sections&action=new' ) ); ?>" class="page-title-action">Añadir nueva</a>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="get">
        <input type="hidden" name="page" value="minicommerce-sections" />
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" width="60">ID</th>
                    <th scope="col"><a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'nombre', 'order' => 'ASC' === $order ? 'DESC' : 'ASC' ) ) ); ?>">Nombre</a></th>
                    <th scope="col">Descripción</th>
                    <th scope="col" width="80">Publicado</th>
                    <th scope="col" width="60"><a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'orden', 'order' => 'ASC' === $order ? 'DESC' : 'ASC' ) ) ); ?>">Orden</a></th>
                    <th scope="col" width="120">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr><td colspan="6">No hay secciones.</td></tr>
                <?php endif; ?>
                <?php foreach ( $items as $s ) : ?>
                    <tr>
                        <td><?php echo esc_html( $s->id ); ?></td>
                        <td><strong><?php echo esc_html( $s->nombre ); ?></strong></td>
                        <td><?php echo esc_html( mb_substr( (string) $s->descripcion, 0, 80 ) ); ?></td>
                        <td><?php echo (int) $s->publicado ? 'Sí' : 'No'; ?></td>
                        <td><?php echo esc_html( $s->orden ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-sections&action=edit&id=' . (int) $s->id ) ); ?>">Editar</a>
                            |
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=minicommerce-sections&action=delete&id=' . (int) $s->id ), 'mc_delete_section_' . $s->id ) ); ?>"
                               onclick="return confirm('¿Eliminar la sección «<?php echo esc_js( $s->nombre ); ?>»?')">Eliminar</a>
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
    </form>
</div>