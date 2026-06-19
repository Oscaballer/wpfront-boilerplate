<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table      = MiniCommerce_DB::table( 'sub_categoria' );
$sec_table  = MiniCommerce_DB::table( 'seccion' );
$per_page   = 20;
$paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$offset     = ( $paged - 1 ) * $per_page;
$filter_sec = isset( $_GET['filter_seccion'] ) ? (int) $_GET['filter_seccion'] : 0;

$where = '';
$wpdb_args = array();
if ( $filter_sec > 0 ) {
    $where = ' WHERE s.id_seccion = %d';
    $wpdb_args[] = $filter_sec;
}

$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} s{$where}", $wpdb_args ) );
$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT s.*, sec.nombre AS seccion_nombre FROM {$table} s LEFT JOIN {$sec_table} sec ON sec.id = s.id_seccion{$where} ORDER BY s.id_seccion, s.nombre LIMIT %d OFFSET %d",
        array_merge( $wpdb_args, array( $per_page, $offset ) )
    )
);
$total_pages = ceil( $total / $per_page );
$secciones   = $wpdb->get_results( "SELECT id, nombre FROM {$sec_table} ORDER BY orden, nombre" );
?>
<div class="wrap mc-wrap">
    <h1 class="wp-heading-inline">Subcategorías</h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-subcats&action=new' ) ); ?>" class="page-title-action">Añadir nueva</a>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="get">
        <input type="hidden" name="page" value="minicommerce-subcats" />
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="filter_seccion">
                    <option value="0">Todas las secciones</option>
                    <?php foreach ( $secciones as $sec ) : ?>
                        <option value="<?php echo esc_attr( $sec->id ); ?>" <?php selected( $filter_sec, (int) $sec->id ); ?>>
                            <?php echo esc_html( $sec->nombre ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="Filtrar" />
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" width="60">ID</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Sección</th>
                    <th scope="col" width="120">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr><td colspan="4">No hay subcategorías.</td></tr>
                <?php endif; ?>
                <?php foreach ( $items as $s ) : ?>
                    <tr>
                        <td><?php echo esc_html( $s->id ); ?></td>
                        <td><strong><?php echo esc_html( $s->nombre ); ?></strong></td>
                        <td><?php echo esc_html( $s->seccion_nombre ?? '—' ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-subcats&action=edit&id=' . (int) $s->id ) ); ?>">Editar</a>
                            |
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=minicommerce-subcats&action=delete&id=' . (int) $s->id ), 'mc_delete_subcat_' . $s->id ) ); ?>"
                               onclick="return confirm('¿Eliminar la subcategoría «<?php echo esc_js( $s->nombre ); ?>»?')">Eliminar</a>
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