<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$status = MiniCommerce_Migrator::get_status();
$images_path = get_option( MiniCommerce_Migrator::OPTION_IMAGE_PATH, '' );
?>
<div class="wrap mc-wrap">
    <h1>Migrador — MiniCommerce</h1>

    <?php settings_errors( 'mc_messages' ); ?>

    <div class="mc-card" style="max-width: 720px; margin-bottom: 24px;">
        <h2>Estado</h2>
        <ul class="mc-stats">
            <li>Última importación BD: <strong><?php echo $status['db_imported_at'] ? esc_html( $status['db_imported_at'] ) : '—'; ?></strong></li>
            <li>Artículos en BD: <strong><?php echo esc_html( (string) $status['counts']['articulos'] ); ?></strong></li>
            <li>Imágenes subidas a GCP: <strong><?php echo esc_html( (string) ( $status['images_uploaded'] ?? 0 ) ); ?></strong></li>
            <li>Imágenes omitidas (ya existían): <strong><?php echo esc_html( (string) ( $status['images_skipped'] ?? 0 ) ); ?></strong></li>
            <li>Imágenes fallidas: <strong><?php echo esc_html( (string) ( $status['images_failed'] ?? 0 ) ); ?></strong></li>
        </ul>
    </div>

    <div class="mc-card" style="max-width: 720px; margin-bottom: 24px;">
        <h2>1. Importar base de datos</h2>
        <p class="description">Sube <code>eros_backup.sql</code>. Trunca las tablas <code>mc_*</code> y reimporta secciones, subcategorías, artículos e imágenes extra.</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mc_admin_action' ); ?>
            <input type="hidden" name="mc_action" value="import_sql" />
            <p><input type="file" name="mc_sql_file" accept=".sql" required /></p>
            <?php submit_button( 'Importar SQL', 'primary', 'submit', false ); ?>
        </form>
    </div>

    <div class="mc-card" style="max-width: 720px; margin-bottom: 24px;">
        <h2>2. Ruta local de imágenes</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'mc_migration_options' ); ?>
            <p>
                <input type="text" name="<?php echo esc_attr( MiniCommerce_Migrator::OPTION_IMAGE_PATH ); ?>"
                       value="<?php echo esc_attr( $images_path ); ?>" class="large-text"
                       placeholder="/ruta/absoluta/old-simple-ecommerce/images/catalogo" />
            </p>
            <p class="description">Ruta en el servidor donde está <code>images/catalogo/</code> del legacy.</p>
            <?php submit_button( 'Guardar ruta', 'secondary' ); ?>
        </form>
    </div>

    <div class="mc-card" style="max-width: 720px;">
        <h2>3. Subir imágenes a GCP</h2>
        <p class="description">Procesa en lotes de 25 archivos. Omite imágenes que ya existen en el bucket.</p>
        <p>
            <button type="button" class="button button-primary" id="mc-upload-images" <?php disabled( empty( $images_path ) || ! mc_gcp()->is_configured() ); ?>>
                Iniciar subida por lotes
            </button>
        </p>
        <div id="mc-upload-progress" style="display:none; margin-top: 12px;">
            <progress id="mc-upload-bar" max="100" value="0" style="width:100%; height: 24px;"></progress>
            <p id="mc-upload-log"></p>
        </div>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('mc-upload-images');
    if (!btn) return;

    const progressWrap = document.getElementById('mc-upload-progress');
    const bar = document.getElementById('mc-upload-bar');
    const log = document.getElementById('mc-upload-log');

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        progressWrap.style.display = 'block';
        log.textContent = 'Iniciando...';
        let offset = 0;
        let total = 0;

        while (true) {
            const body = new URLSearchParams({
                action: 'mc_upload_images_batch',
                nonce: '<?php echo esc_js( wp_create_nonce( 'mc_migration_ajax' ) ); ?>',
                offset: String(offset)
            });

            const res = await fetch(ajaxurl, { method: 'POST', body });
            const data = await res.json();

            if (!data.success) {
                log.textContent = data.data?.message || 'Error en la subida.';
                btn.disabled = false;
                return;
            }

            total = data.data.total || total;
            offset = data.data.offset || 0;
            const pct = total ? Math.min(100, Math.round((offset / total) * 100)) : 100;
            bar.value = pct;
            log.textContent = data.data.message + ' (' + offset + ' / ' + total + ')';

            if (data.data.done) {
                log.textContent += ' — Completado.';
                btn.disabled = false;
                return;
            }
        }
    });
})();
</script>
