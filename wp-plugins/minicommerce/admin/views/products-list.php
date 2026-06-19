<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table      = MiniCommerce_DB::table( 'articulo' );
$sec_table  = MiniCommerce_DB::table( 'seccion' );
$sub_table  = MiniCommerce_DB::table( 'sub_categoria' );
$per_page   = 25;
$paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$offset     = ( $paged - 1 ) * $per_page;

$filter_sec    = isset( $_GET['filter_seccion'] ) ? (int) $_GET['filter_seccion'] : 0;
$filter_subcat = isset( $_GET['filter_subcat'] ) ? (int) $_GET['filter_subcat'] : 0;
$search        = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';

$where   = array( '1=1' );
$wpdb_args = array();

if ( $filter_sec > 0 ) {
    $where[]     = 'a.id_seccion = %d';
    $wpdb_args[] = $filter_sec;
}
if ( $filter_subcat > 0 ) {
    $where[]     = 'a.id_sub_categoria = %d';
    $wpdb_args[] = $filter_subcat;
}
if ( '' !== $search ) {
    $where[]     = '(a.nombre LIKE %s OR a.codigo LIKE %s)';
    $like        = '%' . $wpdb->esc_like( $search ) . '%';
    $wpdb_args[] = $like;
    $wpdb_args[] = $like;
}

$where_clause = implode( ' AND ', $where );

$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} a WHERE {$where_clause}", $wpdb_args ) );
$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT a.*, sec.nombre AS seccion_nombre, sub.nombre AS subcat_nombre
         FROM {$table} a
         LEFT JOIN {$sec_table} sec ON sec.id = a.id_seccion
         LEFT JOIN {$sub_table} sub ON sub.id = a.id_sub_categoria
         WHERE {$where_clause}
         ORDER BY a.orden, a.nombre
         LIMIT %d OFFSET %d",
        array_merge( $wpdb_args, array( $per_page, $offset ) )
    )
);
$total_pages = ceil( $total / $per_page );
$secciones   = $wpdb->get_results( "SELECT id, nombre FROM {$sec_table} ORDER BY orden, nombre" );
?>
<div class="wrap mc-wrap">
    <h1 class="wp-heading-inline">Artículos</h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-products&action=new' ) ); ?>" class="page-title-action">Añadir nuevo</a>

    <?php settings_errors( 'mc_messages' ); ?>
    <?php if ( $total > 0 ) : ?>
        <p class="search-box">
            <form method="get" style="display:inline">
                <input type="hidden" name="page" value="minicommerce-products" />
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar..." />
                <input type="submit" class="button" value="Buscar" />
            </form>
        </p>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="minicommerce-products" />
        <?php if ( '' !== $search ) : ?>
            <input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
        <?php endif; ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="filter_seccion" id="mc-filter-seccion">
                    <option value="0">Todas las secciones</option>
                    <?php foreach ( $secciones as $sec ) : ?>
                        <option value="<?php echo esc_attr( $sec->id ); ?>" <?php selected( $filter_sec, (int) $sec->id ); ?>>
                            <?php echo esc_html( $sec->nombre ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_subcat" id="mc-filter-subcat">
                    <option value="0">Todas las subcategorías</option>
                </select>
                <input type="submit" class="button" value="Filtrar" />
                <?php if ( $filter_sec || $filter_subcat || '' !== $search ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-products' ) ); ?>" class="button">Limpiar filtros</a>
                <?php endif; ?>
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html( $total ); ?> elementos</span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" width="50">ID</th>
                    <th scope="col" width="60">Imagen</th>
                    <th scope="col">Nombre</th>
                    <th scope="col" width="100">Código</th>
                    <th scope="col" width="80">Precio</th>
                    <th scope="col">Sección</th>
                    <th scope="col">Subcategoría</th>
                    <th scope="col" width="50">Novedad</th>
                    <th scope="col" width="50">Ord.</th>
                    <th scope="col" width="100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr><td colspan="10">No hay artículos.</td></tr>
                <?php endif; ?>
                <?php foreach ( $items as $p ) : ?>
                    <tr>
                        <td><?php echo esc_html( $p->id ); ?></td>
                        <td>
                            <?php if ( $p->imagen ) : ?>
                                <img src="<?php echo esc_url( mc_image_url( $p->imagen ) ); ?>"
                                     alt="" style="width:40px;height:40px;object-fit:cover;border-radius:2px;" />
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $p->nombre ); ?></strong></td>
                        <td><?php echo esc_html( $p->codigo ?? '—' ); ?></td>
                        <td><?php echo esc_html( $p->precio ?? '—' ); ?></td>
                        <td><?php echo esc_html( $p->seccion_nombre ?? '—' ); ?></td>
                        <td><?php echo esc_html( $p->subcat_nombre ?? '—' ); ?></td>
                        <td><?php echo (int) $p->novedad === 2 ? 'Sí' : 'No'; ?></td>
                        <td><?php echo esc_html( $p->orden ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-products&action=edit&id=' . (int) $p->id ) ); ?>">Editar</a>
                            |
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=minicommerce-products&action=delete&id=' . (int) $p->id ), 'mc_delete_product_' . $p->id ) ); ?>"
                               onclick="return confirm('¿Eliminar «<?php echo esc_js( $p->nombre ); ?>»?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var secFilter = document.getElementById('mc-filter-seccion');
    var subFilter = document.getElementById('mc-filter-subcat');
    if (!secFilter || !subFilter) return;

    function loadSubcats(seccionId, selectedSubcat) {
        subFilter.innerHTML = '<option value="0">Cargando...</option>';
        var body = new URLSearchParams({
            action: 'mc_get_subcats',
            seccion_id: String(seccionId),
            nonce: '<?php echo esc_js( wp_create_nonce( 'mc_admin_ajax' ) ); ?>'
        });
        fetch(ajaxurl, { method: 'POST', body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    subFilter.innerHTML = '<option value="0">Todas las subcategorías</option>';
                    data.data.forEach(function (sub) {
                        var opt = document.createElement('option');
                        opt.value = sub.id;
                        opt.textContent = sub.nombre;
                        if (selectedSubcat && String(sub.id) === String(selectedSubcat)) {
                            opt.selected = true;
                        }
                        subFilter.appendChild(opt);
                    });
                }
            })
            .catch(function () {
                subFilter.innerHTML = '<option value="0">Todas las subcategorías</option>';
            });
    }

    var initialSubcat = '<?php echo esc_js( (string) $filter_subcat ); ?>';
    if (secFilter.value !== '0') {
        loadSubcats(secFilter.value, initialSubcat);
    }

    secFilter.addEventListener('change', function () {
        var val = this.value;
        if (val === '0') {
            subFilter.innerHTML = '<option value="0">Todas las subcategorías</option>';
        } else {
            loadSubcats(val, null);
        }
    });
});
</script>