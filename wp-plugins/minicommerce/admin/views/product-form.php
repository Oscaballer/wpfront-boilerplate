<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table     = MiniCommerce_DB::table( 'articulo' );
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
$subcats   = array();
if ( $item && $item->id_seccion ) {
    $sub_table = MiniCommerce_DB::table( 'sub_categoria' );
    $subcats   = $wpdb->get_results( $wpdb->prepare( "SELECT id, nombre FROM {$sub_table} WHERE id_seccion = %d ORDER BY nombre", $item->id_seccion ) );
}
?>
<div class="wrap mc-wrap">
    <h1><?php echo $action === 'edit' ? 'Editar artículo' : 'Añadir artículo'; ?></h1>

    <?php settings_errors( 'mc_messages' ); ?>

    <form method="post" enctype="multipart/form-data" class="validate">
        <?php wp_nonce_field( 'mc_admin_action' ); ?>
        <input type="hidden" name="mc_action" value="save_product" />
        <?php if ( $item ) : ?>
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $item->id ); ?>" />
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="prod_nombre">Nombre <span class="required">*</span></label></th>
                <td>
                    <input type="text" id="prod_nombre" name="nombre"
                           value="<?php echo $item ? esc_attr( $item->nombre ) : ''; ?>"
                           class="regular-text" required />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_codigo">Código / SKU</label></th>
                <td>
                    <input type="text" id="prod_codigo" name="codigo"
                           value="<?php echo $item ? esc_attr( $item->codigo ) : ''; ?>"
                           class="regular-text" placeholder="VE-097" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_descripcion">Descripción</label></th>
                <td>
                    <textarea id="prod_descripcion" name="descripcion" rows="6" class="large-text"><?php echo $item ? esc_textarea( $item->descripcion ) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_precio">Precio</label></th>
                <td>
                    <input type="text" id="prod_precio" name="precio"
                           value="<?php echo $item ? esc_attr( $item->precio ) : ''; ?>"
                           class="regular-text" placeholder="Gs. 50.000" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_id_seccion">Sección</label></th>
                <td>
                    <select id="prod_id_seccion" name="id_seccion">
                        <option value="0">Sin sección</option>
                        <?php foreach ( $secciones as $sec ) : ?>
                            <option value="<?php echo esc_attr( $sec->id ); ?>"
                                <?php selected( $item ? (int) $item->id_seccion : 0, (int) $sec->id ); ?>>
                                <?php echo esc_html( $sec->nombre ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_id_sub_categoria">Subcategoría</label></th>
                <td>
                    <select id="prod_id_sub_categoria" name="id_sub_categoria"
                            data-selected="<?php echo $item ? esc_attr( $item->id_sub_categoria ) : '0'; ?>">
                        <option value="0">Sin subcategoría</option>
                        <?php if ( $item ) : ?>
                            <?php foreach ( $subcats as $sub ) : ?>
                                <option value="<?php echo esc_attr( $sub->id ); ?>"
                                    <?php selected( (int) $item->id_sub_categoria, (int) $sub->id ); ?>>
                                    <?php echo esc_html( $sub->nombre ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description">Selecciona primero la sección para cargar las subcategorías.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_novedad">Novedad</label></th>
                <td>
                    <select id="prod_novedad" name="novedad">
                        <option value="0" <?php selected( $item ? (int) $item->novedad : 0, 0 ); ?>>Normal</option>
                        <option value="1" <?php selected( $item ? (int) $item->novedad : 0, 1 ); ?>>Destacado</option>
                        <option value="2" <?php selected( $item ? (int) $item->novedad : 0, 2 ); ?>>Novedad</option>
                    </select>
                    <p class="description">Valor 2 = novedad activa (<code>isNew: true</code> en GraphQL).</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_orden">Orden</label></th>
                <td>
                    <input type="number" id="prod_orden" name="orden" min="0" step="1"
                           value="<?php echo $item ? esc_attr( $item->orden ) : '0'; ?>" style="width:80px" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_publicado">Publicado</label></th>
                <td>
                    <input type="checkbox" id="prod_publicado" name="publicado" value="1"
                           <?php checked( $item ? (int) $item->publicado : 1, 1 ); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prod_imagen">Imagen principal</label></th>
                <td>
                    <?php if ( $item && $item->imagen ) : ?>
                        <p style="margin-bottom:8px">
                            <img src="<?php echo esc_url( mc_image_url( $item->imagen ) ); ?>"
                                 alt="" style="max-width:150px;max-height:150px;border-radius:4px;" />
                            <br />
                            <label>
                                <input type="checkbox" name="eliminar_imagen" value="1" />
                                Eliminar imagen actual
                            </label>
                        </p>
                    <?php endif; ?>
                    <input type="file" id="prod_imagen" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" />
                    <p class="description">Se subirá automáticamente a GCP. Formatos: JPG, PNG, GIF, WebP.</p>
                </td>
            </tr>
        </table>

        <?php submit_button( $action === 'edit' ? 'Actualizar artículo' : 'Crear artículo' ); ?>
    </form>

    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-products' ) ); ?>">&larr; Volver a artículos</a></p>

    <?php if ( $item ) : ?>
        <hr />
        <h2>Imágenes adicionales</h2>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-images&product_id=' . (int) $item->id ) ); ?>" class="button button-secondary">Gestionar galería</a></p>
    <?php endif; ?>
</div>