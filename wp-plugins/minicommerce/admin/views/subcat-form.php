<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table     = MiniCommerce_DB::table( 'sub_categoria' );
$sec_table = MiniCommerce_DB::table( 'seccion' );
$item      = null;
$action    = 'new';

if ( isset( $_GET['id'] ) ) {
    $id   = (int) $_GET['id'];
    $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    if ( $item ) {
        $action = 'edit';
    }
}

$secciones = $wpdb->get_results( "SELECT id, nombre FROM {$sec_table} ORDER BY orden, nombre" );
?>
<div class="wrap mc-wrap">
    <h1><?php echo $action === 'edit' ? 'Editar subcategoría' : 'Añadir subcategoría'; ?></h1>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="post" class="validate">
        <?php wp_nonce_field( 'mc_admin_action' ); ?>
        <input type="hidden" name="mc_action" value="save_subcat" />
        <?php if ( $item ) : ?>
            <input type="hidden" name="subcat_id" value="<?php echo esc_attr( $item->id ); ?>" />
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="subcat_nombre">Nombre <span class="required">*</span></label></th>
                <td>
                    <input type="text" id="subcat_nombre" name="nombre"
                           value="<?php echo $item ? esc_attr( $item->nombre ) : ''; ?>"
                           class="regular-text" required />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="subcat_id_seccion">Sección <span class="required">*</span></label></th>
                <td>
                    <select id="subcat_id_seccion" name="id_seccion" required>
                        <option value="">Seleccionar sección</option>
                        <?php foreach ( $secciones as $sec ) : ?>
                            <option value="<?php echo esc_attr( $sec->id ); ?>"
                                <?php selected( $item ? (int) $item->id_seccion : 0, (int) $sec->id ); ?>>
                                <?php echo esc_html( $sec->nombre ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button( $action === 'edit' ? 'Actualizar subcategoría' : 'Crear subcategoría' ); ?>
    </form>

    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-subcats' ) ); ?>">&larr; Volver a subcategorías</a></p>
</div>