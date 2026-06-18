<?php
/**
 * Plugin Name: FDCS Transparencia Manager (GCP)
 * Description: Administra los archivos de transparencia alojados en Google Cloud Storage directamente desde WordPress.
 * Version: 1.0.0
 * Author: Oscar Caballero & Antigravity AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================================
// 1. CONFIGURACIÓN Y MENÚS
// ==========================================

function fdcs_transparencia_menu() {
    add_menu_page(
        'Transparencia',
        'Transparencia',
        'manage_options',
        'fdcs-transparencia',
        'fdcs_transparencia_admin_page',
        'dashicons-portfolio',
        30
    );

    add_submenu_page(
        'fdcs-transparencia',
        'Ajustes GCP',
        'Ajustes GCP',
        'manage_options',
        'fdcs-transparencia-settings',
        'fdcs_transparencia_settings_page'
    );
}
add_action('admin_menu', 'fdcs_transparencia_menu');

function fdcs_transparencia_register_settings() {
    register_setting('fdcs_transparencia_options', 'fdcs_gcp_bucket');
    register_setting('fdcs_transparencia_options', 'fdcs_gcp_json');
}
add_action('admin_init', 'fdcs_transparencia_register_settings');

// ==========================================
// 2. AUTENTICACIÓN GCP (Sin dependencias)
// ==========================================

function fdcs_get_gcp_token() {
    $transient_key = 'fdcs_gcp_access_token';
    $token = get_transient($transient_key);
    if ($token) return $token;

    $json_string = get_option('fdcs_gcp_json');
    if (!$json_string) return false;

    $sa = json_decode($json_string, true);
    if (!$sa || !isset($sa['client_email']) || !isset($sa['private_key'])) return false;

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlEncode = function($text) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    };

    $signature_input = $base64UrlEncode($header) . '.' . $base64UrlEncode($claim);
    $signature = '';
    openssl_sign($signature_input, $signature, $sa['private_key'], 'SHA256');

    $jwt = $signature_input . '.' . $base64UrlEncode($signature);

    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'body' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]
    ]);

    if (is_wp_error($response)) return false;
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['access_token'])) {
        set_transient($transient_key, $body['access_token'], 3000); // Cache por 50 min
        return $body['access_token'];
    }
    
    return false;
}

// ==========================================
// 3. PÁGINA DE AJUSTES
// ==========================================

function fdcs_transparencia_settings_page() {
    ?>
    <div class="wrap">
        <h1>Ajustes de Google Cloud Storage</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fdcs_transparencia_options'); ?>
            <?php do_settings_sections('fdcs_transparencia_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Nombre del Bucket GCP<br><small>(Ej: media.derechoune.edu.py)</small></th>
                    <td><input type="text" name="fdcs_gcp_bucket" value="<?php echo esc_attr(get_option('fdcs_gcp_bucket')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Service Account JSON<br><small>Pega el contenido completo del archivo JSON descargado desde Google Cloud.</small></th>
                    <td><textarea name="fdcs_gcp_json" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('fdcs_gcp_json')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ==========================================
// 4. LÓGICA DE EXPLORADOR Y GESTIÓN
// ==========================================

function fdcs_handle_file_actions() {
    if (!isset($_POST['fdcs_action']) || !current_user_can('manage_options')) return;
    check_admin_referer('fdcs_transparencia_nonce');

    $bucket = get_option('fdcs_gcp_bucket');
    $token = fdcs_get_gcp_token();

    if (!$bucket || !$token) {
        add_settings_error('fdcs_messages', 'fdcs_error', 'Falta configuración o error de token GCP.', 'error');
        return;
    }

    $action = $_POST['fdcs_action'];
    
    // UPLOAD FILE
    if ($action === 'upload') {
        $folder = sanitize_text_field($_POST['folder_path'] ?? 'transparencia/');
        if (!empty($_FILES['gcp_file']['tmp_name'])) {
            $file = $_FILES['gcp_file'];
            $file_name = sanitize_file_name($file['name']);
            $object_name = $folder . $file_name;
            $file_content = file_get_contents($file['tmp_name']);
            
            $upload_url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=media&name=" . urlencode($object_name);
            
            $response = wp_remote_post($upload_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => $file['type']
                ],
                'body' => $file_content,
                'timeout' => 60
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
                add_settings_error('fdcs_messages', 'fdcs_error', 'Error al subir el archivo.', 'error');
            } else {
                add_settings_error('fdcs_messages', 'fdcs_success', 'Archivo subido con éxito.', 'updated');
            }
        }
    }

    // CREATE FOLDER
    if ($action === 'create_folder') {
        $parent = sanitize_text_field($_POST['parent_folder'] ?? 'transparencia/');
        $new_folder = sanitize_text_field($_POST['new_folder_name']);
        if (!empty($new_folder)) {
            $object_name = $parent . $new_folder . '/'; // Trailing slash creates a "folder"
            
            $upload_url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=media&name=" . urlencode($object_name);
            
            $response = wp_remote_post($upload_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => ''
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
                add_settings_error('fdcs_messages', 'fdcs_error', 'Error al crear carpeta.', 'error');
            } else {
                add_settings_error('fdcs_messages', 'fdcs_success', 'Carpeta creada.', 'updated');
            }
        }
    }

    // DELETE OBJECT
    if ($action === 'delete') {
        $object_name = stripslashes($_POST['object_name']); // Full path like "transparencia/folder/file.pdf"
        if (!empty($object_name)) {
            $delete_url = "https://storage.googleapis.com/storage/v1/b/{$bucket}/o/" . urlencode($object_name);
            
            $response = wp_remote_request($delete_url, [
                'method' => 'DELETE',
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
                add_settings_error('fdcs_messages', 'fdcs_error', 'Error al eliminar.', 'error');
            } else {
                add_settings_error('fdcs_messages', 'fdcs_success', 'Elemento eliminado.', 'updated');
            }
        }
    }
}
add_action('admin_init', 'fdcs_handle_file_actions');

// ==========================================
// 5. INTERFAZ DEL GESTOR
// ==========================================

function fdcs_transparencia_admin_page() {
    $bucket = get_option('fdcs_gcp_bucket');
    if (!$bucket) {
        echo '<div class="wrap"><h1>Gestor de Transparencia</h1><div class="notice notice-warning"><p>Por favor, configura el Bucket GCP y el JSON en la página de Ajustes GCP.</p></div></div>';
        return;
    }

    settings_errors('fdcs_messages');

    // Get files list
    $url = "https://storage.googleapis.com/storage/v1/b/{$bucket}/o?prefix=transparencia/&delimiter=/";
    $current_prefix = isset($_GET['prefix']) ? sanitize_text_field($_GET['prefix']) : 'transparencia/';
    $list_url = "https://storage.googleapis.com/storage/v1/b/{$bucket}/o?prefix=" . urlencode($current_prefix) . "&delimiter=/";
    
    $response = wp_remote_get($list_url);
    $items = [];
    $prefixes = [];
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['items'])) $items = $body['items'];
        if (isset($body['prefixes'])) $prefixes = $body['prefixes'];
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Archivos de Transparencia (GCP)</h1>
        
        <div style="display:flex; gap: 20px; margin-top: 20px;">
            <!-- SUBIR ARCHIVO -->
            <div class="card" style="max-width: 400px;">
                <h3>Subir Archivo</h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('fdcs_transparencia_nonce'); ?>
                    <input type="hidden" name="fdcs_action" value="upload">
                    <input type="hidden" name="folder_path" value="<?php echo esc_attr($current_prefix); ?>">
                    <p>Sube un archivo en: <strong><?php echo esc_html($current_prefix); ?></strong></p>
                    <p><input type="file" name="gcp_file" required></p>
                    <p><input type="submit" class="button button-primary" value="Subir"></p>
                </form>
            </div>

            <!-- CREAR CARPETA -->
            <div class="card" style="max-width: 400px;">
                <h3>Nueva Carpeta</h3>
                <form method="post">
                    <?php wp_nonce_field('fdcs_transparencia_nonce'); ?>
                    <input type="hidden" name="fdcs_action" value="create_folder">
                    <input type="hidden" name="parent_folder" value="<?php echo esc_attr($current_prefix); ?>">
                    <p>Crear dentro de: <strong><?php echo esc_html($current_prefix); ?></strong></p>
                    <p><input type="text" name="new_folder_name" placeholder="Nombre de la carpeta" required class="regular-text"></p>
                    <p><input type="submit" class="button" value="Crear"></p>
                </form>
            </div>
        </div>

        <hr>

        <h2>Explorador</h2>
        <p>Ruta actual: <strong><?php echo esc_html($current_prefix); ?></strong></p>
        
        <?php if ($current_prefix !== 'transparencia/'): 
            $parts = explode('/', rtrim($current_prefix, '/'));
            array_pop($parts);
            $up = implode('/', $parts) . '/';
        ?>
            <p><a href="?page=fdcs-transparencia&prefix=<?php echo urlencode($up); ?>" class="button">&larr; Volver arriba</a></p>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 50px;">Tipo</th>
                    <th>Nombre</th>
                    <th>Tamaño</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Render Folders
                foreach ($prefixes as $prefix): 
                    $folder_name = basename($prefix);
                ?>
                    <tr>
                        <td><span class="dashicons dashicons-category"></span></td>
                        <td><strong><a href="?page=fdcs-transparencia&prefix=<?php echo urlencode($prefix); ?>"><?php echo esc_html($folder_name); ?></a></strong></td>
                        <td>-</td>
                        <td>
                            <!-- No se pueden borrar carpetas llenas fácilmente, así que no damos el botón por ahora para evitar problemas GCP -->
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php 
                // Render Files
                foreach ($items as $item): 
                    if ($item['name'] === $current_prefix) continue; // Skip the folder object itself
                    $file_name = basename($item['name']);
                    $size_kb = round($item['size'] / 1024, 2);
                ?>
                    <tr>
                        <td><span class="dashicons dashicons-media-document"></span></td>
                        <td><a href="<?php echo esc_url("https://{$bucket}/" . $item['name']); ?>" target="_blank"><?php echo esc_html($file_name); ?></a></td>
                        <td><?php echo $size_kb; ?> KB</td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este archivo?');">
                                <?php wp_nonce_field('fdcs_transparencia_nonce'); ?>
                                <input type="hidden" name="fdcs_action" value="delete">
                                <input type="hidden" name="object_name" value="<?php echo esc_attr($item['name']); ?>">
                                <input type="submit" class="button button-link-delete" value="Eliminar" style="color: #d63638;">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($items) && empty($prefixes)): ?>
                    <tr>
                        <td colspan="4">Carpeta vacía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
