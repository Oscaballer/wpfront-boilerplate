<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap mc-wrap">
    <h1>MiniCommerce</h1>

    <div class="mc-cards">
        <div class="mc-card">
            <h2>Estado del catálogo</h2>
            <ul class="mc-stats">
                <li><strong><?php echo esc_html( (string) $counts['articulos'] ); ?></strong> artículos</li>
                <li><strong><?php echo esc_html( (string) $counts['secciones'] ); ?></strong> secciones</li>
                <li><strong><?php echo esc_html( (string) $counts['subcategorias'] ); ?></strong> subcategorías</li>
                <li><strong><?php echo esc_html( (string) $counts['imagenes_extra'] ); ?></strong> imágenes adicionales</li>
            </ul>
        </div>

        <div class="mc-card">
            <h2>Integraciones</h2>
            <p>
                GCP:
                <?php if ( $gcp_ok ) : ?>
                    <span class="mc-badge mc-badge--ok">Configurado</span>
                <?php else : ?>
                    <span class="mc-badge mc-badge--warn">Pendiente</span>
                    — <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-gcp' ) ); ?>">Configurar</a>
                <?php endif; ?>
            </p>
            <p>
                WhatsApp:
                <?php $wa = get_option( 'mc_whatsapp_number', '' ); ?>
                <?php if ( $wa ) : ?>
                    <span class="mc-badge mc-badge--ok"><?php echo esc_html( $wa ); ?></span>
                <?php else : ?>
                    <span class="mc-badge mc-badge--warn">Sin número</span>
                    — <a href="<?php echo esc_url( admin_url( 'admin.php?page=minicommerce-settings' ) ); ?>">Configurar</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <p class="description">
        Frontend headless en <code>eros.com.py/tienda</code> — CMS en <code>cms.eros.com.py</code>.
        Imágenes en <code><?php echo esc_html( MC_GCP_PREFIX ); ?></code> del bucket GCP.
    </p>
</div>
