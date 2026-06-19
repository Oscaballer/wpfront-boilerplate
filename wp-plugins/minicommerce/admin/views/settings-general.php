<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
settings_errors( 'mc_messages' );
?>
<div class="wrap mc-wrap">
    <h1>Ajustes generales</h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'mc_general_options' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="mc_whatsapp_number">WhatsApp pedidos</label></th>
                <td>
                    <input type="text" id="mc_whatsapp_number" name="mc_whatsapp_number"
                           value="<?php echo esc_attr( get_option( 'mc_whatsapp_number', '' ) ); ?>"
                           class="regular-text" placeholder="595981234567" />
                    <p class="description">Número internacional sin espacios. El carrito del frontend generará un enlace wa.me con el pedido.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mc_revalidate_url">URL revalidación ISR</label></th>
                <td>
                    <input type="url" id="mc_revalidate_url" name="<?php echo esc_attr( MiniCommerce_Revalidate::OPTION_URL ); ?>"
                           value="<?php echo esc_attr( get_option( MiniCommerce_Revalidate::OPTION_URL, 'https://eros.com.py/api/revalidate' ) ); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mc_revalidate_secret">Secret revalidación</label></th>
                <td>
                    <input type="password" id="mc_revalidate_secret" name="<?php echo esc_attr( MiniCommerce_Revalidate::OPTION_SECRET ); ?>"
                           value="<?php echo esc_attr( get_option( MiniCommerce_Revalidate::OPTION_SECRET, '' ) ); ?>"
                           class="regular-text" autocomplete="new-password" />
                    <p class="description">Mismo valor que <code>REVALIDATE_SECRET</code> en el frontend Next.js.</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Guardar ajustes' ); ?>
    </form>

    <hr>

    <h2>Probar revalidación</h2>
    <form method="post">
        <?php wp_nonce_field( 'mc_admin_action' ); ?>
        <input type="hidden" name="mc_action" value="test_revalidate" />
        <?php submit_button( 'Enviar tag minicommerce', 'secondary' ); ?>
    </form>
</div>
