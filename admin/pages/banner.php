<?php
if (!defined('ABSPATH')) {
    exit;
}

function custom_banner_page() {
    ?>
    <div class="wrap">
        <h1 class="mb-4">Configuración del Banner</h1>
        
        <div class="container">
            <div class="card shadow-md">
                <div class="card-body">
                    <?php
                    // Guardar los datos si el formulario es enviado
                    if (isset($_POST['submit'])) {
                        update_option('custom_banner_name', sanitize_text_field($_POST['banner_name']));
                        update_option('custom_banner_description', sanitize_text_field($_POST['banner_description']));
                        update_option('custom_banner_url', esc_url_raw($_POST['banner_url']));
                        update_option('custom_banner_position', sanitize_text_field($_POST['banner_position']));
                        update_option('custom_banner_views', intval($_POST['banner_views']));
                        update_option('custom_banner_start_date', sanitize_text_field($_POST['banner_start_date']));
                        update_option('custom_banner_end_date', sanitize_text_field($_POST['banner_end_date']));
                        update_option('custom_banner_active', isset($_POST['banner_active']) ? 1 : 0);

                        if (!function_exists('wp_handle_upload')) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                        }

                        $uploadedfile = $_FILES['banner_image'];
                        $upload_overrides = array('test_form' => false);
                        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

                        if ($movefile && !isset($movefile['error'])) {
                            $filename = $movefile['file'];
                            $filetype = wp_check_filetype($filename, null);

                            $attachment = array(
                                'guid'           => $movefile['url'],
                                'post_mime_type' => $filetype['type'],
                                'post_title'     => sanitize_file_name($uploadedfile['name']),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );

                            $attach_id = wp_insert_attachment($attachment, $filename);
                            require_once ABSPATH . 'wp-admin/includes/image.php';
                            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                            wp_update_attachment_metadata($attach_id, $attach_data);

                            update_option('custom_banner_image_id', $attach_id);
                            echo '<div class="alert alert-success">Datos guardados con éxito.</div>';
                        }
                    }

                    $banner_name = get_option('custom_banner_name', '');
                    $banner_description = get_option('custom_banner_description', '');
                    $banner_url = get_option('custom_banner_url', '');
                    $banner_position = get_option('custom_banner_position', '');
                    $banner_views = get_option('custom_banner_views', '');
                    $banner_start_date = get_option('custom_banner_start_date', '');
                    $banner_end_date = get_option('custom_banner_end_date', '');
                    $banner_active = get_option('custom_banner_active', 0);
                    $banner_id = get_option('custom_banner_image_id', '');
                    $banner_image_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
                    ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="banner_name" class="form-label">Nombre</label>
                                    <input type="text" name="banner_name" id="banner_name" value="<?php echo esc_attr($banner_name); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_description" class="form-label">Descripción</label>
                                    <input type="text" name="banner_description" id="banner_description" value="<?php echo esc_attr($banner_description); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_url" class="form-label">URL</label>
                                    <input type="text" name="banner_url" id="banner_url" value="<?php echo esc_attr($banner_url); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_position" class="form-label">Posición</label>
                                    <select name="banner_position" id="banner_position" class="form-select">
                                        <option value="top" <?php selected($banner_position, 'top'); ?>>Arriba</option>
                                        <option value="bottom" <?php selected($banner_position, 'bottom'); ?>>Abajo</option>
                                        <option value="sidebar" <?php selected($banner_position, 'sidebar'); ?>>Barra Lateral</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner_views" class="form-label">Vistas</label>
                                    <input type="number" name="banner_views" id="banner_views" value="<?php echo esc_attr($banner_views); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_start_date" class="form-label">Fecha Inicio</label>
                                    <input type="date" name="banner_start_date" id="banner_start_date" value="<?php echo esc_attr($banner_start_date); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_end_date" class="form-label">Fecha Fin</label>
                                    <input type="date" name="banner_end_date" id="banner_end_date" value="<?php echo esc_attr($banner_end_date); ?>" class="form-control">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="banner_active" id="banner_active" value="1" <?php checked($banner_active, 1); ?>>
                                    <label class="form-check-label" for="banner_active">Activo</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="banner_image" class="form-label">Subir Imagen</label>
                            <input type="file" name="banner_image" id="banner_image" accept="image/*" class="form-control">
                            <?php if ($banner_image_url) : ?>
                                <div class="mt-3">
                                    <img src="<?php echo esc_url($banner_image_url); ?>" alt="Banner" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary">Guardar Configuración</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Agregar la página al menú de administración
function custom_banner_menu() {
    add_menu_page('Banner Config', 'Banner', 'manage_options', 'custom-banner', 'custom_banner_page', 'dashicons-format-image', 20);
}
add_action('admin_menu', 'custom_banner_menu');
