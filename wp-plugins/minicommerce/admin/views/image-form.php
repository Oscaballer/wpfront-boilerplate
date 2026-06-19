<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$img_table  = MiniCommerce_DB::table( 'imagen_articulo' );
$prod_table = MiniCommerce_DB::table( 'articulo' );
$item       = null;
$action     = 'new';
$product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;

if ( isset( $_GET['id'] ) ) {
    $id   = (int) $_GET['id'];
    $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$img_table} WHERE id = %d", $id ) );
    if ( $item ) {
        $action     = 'edit';
        $product_id = (int) $item->id_articulo;
    }
}

$product = null;
if ( $product_id ) {
    $product = $wpdb->get_row( $wpdb->prepare( "SELECT id, nombre FROM {$prod_table} WHERE id = %d", $product_id ) );
}
?>
<div class="wrap mc-wrap">
    <h1><?php echo $action === 'edit' ? 'Editar imagen' : 'Añadir imagen'; ?></h1>

    <?php if ( $product ) : ?>
        <p>Artículo: <strong><?php echo esc_html( $product->nombre ); ?></strong></p>
    <?php endif; ?>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="post" enctype="multipart/form-data" class="validate">
        <?php wp_nonce_field( 'mc_admin_action' ); ?>
        <input type="hidden" name="mc_action" value="save_image" />
        <?php if ( $item ) : ?>
            <input type="hidden" name="image_id" value="<?php echo esc_attr( $item->id ); ?>" />
        <?php endif; ?>
        <input type="hidden" name="id_articulo" value="<?php echo esc_attr( $product_id ); ?>" />

        <table class="form-table">
            <?php if ( ! $product_id ) : ?>
            <tr>
                <th scope="row"><label for="img_id_articulo">Artículo ID <span class="required">*</span></label></th>
                <td>
                    <input type="number" id="img_id_articulo" name="id_articulo" min="1" required
                           value="<?php echo $item ? esc_attr( $item->id_articulo ) : ''; ?>" />
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th scope="row"><label for="img_imagen">Archivo de imagen <span class="required">*</span></label></th>
                <td>
                    <?php if ( $item && $item->imagen ) : ?>
                        <p style="margin-bottom:8px">
                            <img src="<?php echo esc_url( mc_image_url( $item->imagen ) ); ?>"
                                 alt="" style="max-width:150px;max-height:150px;border-radius:4px;" />
                        </p>
                    <?php endif; ?>
                    <input type="file" id="img_imagen" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp"
                           <?php echo $action === 'new' ? 'required' : ''; ?> />
                    <p class="description">Se subirá a GCP. Si editas, deja vacío para mantener la imagen actual.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="img_descripcion">Descripción / texto alternativo</label></th>
                <td>
                    <input type="text" id="img_descripcion" name="descripcion" maxlength="250"
                           value="<?php echo $item ? esc_attr( $item->descripcion ) : ''; ?>"
                           class="regular-text" />
                </td>
            </tr>
        </table>

        <?php submit_button( $action === 'edit' ? 'Actualizar imagen' : 'Subir imagen' ); ?>
    </form>

    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-images' . ( $product_id ? '&product_id=' . $product_id : '' ) ) ); ?>">&larr; Volver a imágenes</a></p>
</div>