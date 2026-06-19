<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table  = MiniCommerce_DB::table( 'seccion' );
$item   = null;
$action = 'new';

if ( isset( $_GET['id'] ) ) {
    $id   = (int) $_GET['id'];
    $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    if ( $item ) {
        $action = 'edit';
    }
}
?>
<div class="wrap mc-wrap">
    <h1><?php echo $action === 'edit' ? 'Editar sección' : 'Añadir sección'; ?></h1>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="post" class="validate">
        <?php wp_nonce_field( 'mc_admin_action' ); ?>
        <input type="hidden" name="mc_action" value="save_section" />
        <?php if ( $item ) : ?>
            <input type="hidden" name="section_id" value="<?php echo esc_attr( $item->id ); ?>" />
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="section_nombre">Nombre <span class="required">*</span></label></th>
                <td>
                    <input type="text" id="section_nombre" name="nombre"
                           value="<?php echo $item ? esc_attr( $item->nombre ) : ''; ?>"
                           class="regular-text" required />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="section_descripcion">Descripción</label></th>
                <td>
                    <textarea id="section_descripcion" name="descripcion" rows="4" class="large-text"><?php echo $item ? esc_textarea( $item->descripcion ) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="section_publicado">Publicado</label></th>
                <td>
                    <input type="checkbox" id="section_publicado" name="publicado" value="1"
                           <?php checked( $item ? (int) $item->publicado : 1, 1 ); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="section_orden">Orden</label></th>
                <td>
                    <input type="number" id="section_orden" name="orden" min="0" step="1"
                           value="<?php echo $item ? esc_attr( $item->orden ) : '0'; ?>" style="width:80px" />
                </td>
            </tr>
        </table>

        <?php submit_button( $action === 'edit' ? 'Actualizar sección' : 'Crear sección' ); ?>
    </form>
</div>