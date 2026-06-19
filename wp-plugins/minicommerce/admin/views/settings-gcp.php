<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
settings_errors( 'mc_messages' );
settings_errors( 'mc_gcp_options' );
$host = get_option( MiniCommerce_GCP_Storage::OPTION_HOST, MC_DEFAULT_GCP_HOST );
?>
<div class="wrap mc-wrap">
    <h1>Ajustes GCP — MiniCommerce</h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'mc_gcp_options' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="mc_gcp_bucket">Nombre del bucket</label></th>
                <td>
                    <input type="text" id="mc_gcp_bucket" name="<?php echo esc_attr( MiniCommerce_GCP_Storage::OPTION_BUCKET ); ?>"
                           value="<?php echo esc_attr( get_option( MiniCommerce_GCP_Storage::OPTION_BUCKET, '' ) ); ?>"
                           class="regular-text" placeholder="media.eros.com.py" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mc_gcp_public_host">URL pública del bucket</label></th>
                <td>
                    <input type="url" id="mc_gcp_public_host" name="<?php echo esc_attr( MiniCommerce_GCP_Storage::OPTION_HOST ); ?>"
                           value="<?php echo esc_attr( $host ); ?>"
                           class="regular-text" />
                    <p class="description">Ejemplo: <code>https://media.eros.com.py</code></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mc_gcp_json">Service Account JSON</label></th>
                <td>
                    <textarea id="mc_gcp_json" name="<?php echo esc_attr( MiniCommerce_GCP_Storage::OPTION_JSON ); ?>"
                              rows="10" class="large-text code"><?php echo esc_textarea( get_option( MiniCommerce_GCP_Storage::OPTION_JSON, '' ) ); ?></textarea>
                    <p class="description">Rol recomendado: <code>roles/storage.objectAdmin</code>. Prefijo de objetos: <code><?php echo esc_html( MC_GCP_PREFIX ); ?></code></p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Guardar ajustes GCP' ); ?>
    </form>

    <?php if ( mc_gcp()->is_configured() ) : ?>
        <hr>
        <h2>Probar subida</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mc_admin_action' ); ?>
            <input type="hidden" name="mc_action" value="test_gcp_upload" />
            <p>
                <input type="file" name="mc_test_image" accept="image/jpeg,image/png,image/gif,image/webp" required />
            </p>
            <?php submit_button( 'Subir imagen de prueba a GCP', 'secondary' ); ?>
        </form>
    <?php endif; ?>
</div>
