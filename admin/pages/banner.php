<?php
if (!defined('ABSPATH')) {
    exit;
}

function custom_banner_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    $items_per_page = 5; // Número de elementos por página
    $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Obtener el total de registros
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Obtener los registros con límite y desplazamiento
    $banners = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name LIMIT %d OFFSET %d", $items_per_page, $offset));

    // Calcular el total de páginas
    $total_pages = ceil($total_items / $items_per_page);
    ?>
    <div class="wrap">
        <h1 class="mb-4"><strong>Configuración del Banner</strong></h1>
        
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title"><strong>Crea tu banner</strong></h5>
                </div>
                <div class="card-body">
                    <?php
                    // Si se envió el formulario...
                    if (isset($_POST['submit'])) {
                        $banner_name = sanitize_text_field($_POST['banner_name']);
                        $banner_description = sanitize_text_field($_POST['banner_description']);
                        $banner_url = esc_url_raw($_POST['banner_url']);
                        $banner_position = sanitize_text_field($_POST['banner_position']);
                        $banner_views = intval($_POST['banner_views']);
                        $banner_start_date = sanitize_text_field($_POST['banner_start_date']);
                        $banner_end_date = sanitize_text_field($_POST['banner_end_date']);
                        $banner_active = 1;

                        if (!function_exists('wp_handle_upload')) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                        }

                        $uploadedfile_desktop = $_FILES['banner_image_desktop'];
                        $uploadedfile_mobile = $_FILES['banner_image_mobile'];
                        $upload_overrides = array('test_form' => false);
                        $movefile_desktop = wp_handle_upload($uploadedfile_desktop, $upload_overrides);
                        $movefile_mobile = wp_handle_upload($uploadedfile_mobile, $upload_overrides);

                        if ($movefile_desktop && !isset($movefile_desktop['error']) && $movefile_mobile && !isset($movefile_mobile['error'])) {
                            $path_desktop = $movefile_desktop['url'];
                            $path_mobile = $movefile_mobile['url'];

                            $wpdb->insert(
                                $table_name,
                                array(
                                    'name' => $banner_name,
                                    'description' => $banner_description,
                                    'path_desktop' => $path_desktop,
                                    'path_mobile' => $path_mobile,
                                    'url' => $banner_url,
                                    'position' => $banner_position,
                                    'views' => $banner_views,
                                    'remaining_views' => $banner_views,
                                    'init_date' => $banner_start_date,
                                    'end_date' => $banner_end_date,
                                    'active' => $banner_active
                                )
                            );

                            echo '<div class="alert alert-success">Datos guardados con éxito.</div>';
                            // Se ejecuta un script para resetear el formulario y limpiar la previsualización
                            echo '<script>document.addEventListener("DOMContentLoaded", function() { resetForm(); clearImagePreview(); });</script>';
                        } else {
                            echo '<div class="alert alert-danger">Error al subir las imágenes.</div>';
                        }
                    }
                    ?>

                    <form method="post" enctype="multipart/form-data" id="banner-form" onsubmit="clearImagePreview()">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner_name" class="form-label">Nombre *</label>
                                    <input type="text" name="banner_name" id="banner_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="banner_views" class="form-label">Vistas</label>
                                    <input type="number" name="banner_views" id="banner_views" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_url" class="form-label">URL *</label>
                                    <input type="text" name="banner_url" id="banner_url" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="banner_position" class="form-label">Posición *</label>
                                    <select name="banner_position" id="banner_position" class="form-select" required>
                                        <option value="top">Arriba</option>
                                        <option value="bottom">Abajo</option>
                                        <option value="sidebar">Barra Lateral</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner_description" class="form-label">Descripción *</label>
                                    <input type="text" name="banner_description" id="banner_description" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="banner_start_date" class="form-label">Fecha Inicio</label>
                                    <input type="date" name="banner_start_date" id="banner_start_date" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="banner_end_date" class="form-label">Fecha Fin</label>
                                    <input type="date" name="banner_end_date" id="banner_end_date" class="form-control">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner_image_desktop" class="form-label">Subir imagen desktop *</label>
                                    <input type="file" name="banner_image_desktop" id="banner_image_desktop" accept="image/*" class="form-control" onchange="previewImageDesktop(event)" required>
                                    <div class="mt-3" id="image-preview-desktop"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner_image_mobile" class="form-label">Subir imagen mobile *</label>
                                    <input type="file" name="banner_image_mobile" id="banner_image_mobile" accept="image/*" class="form-control" onchange="previewImageMobile(event)" required>
                                    <div class="mt-3" id="image-preview-mobile"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="submit" class="btn btn-primary">Guardar banner</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla de Banners -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="card-title"><strong>Banners creados</strong></h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>URL</th>
                            <th>Posición</th>
                            <th>Vistas</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Banner mobile</th>
                            <th>Banner desktop</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener los banners guardados
                        $banners = $wpdb->get_results("SELECT * FROM $table_name");
                        foreach ($banners as $banner) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($banner->name); ?></td>
                                <td><?php echo esc_html($banner->description); ?></td>
                                <td><a href="<?php echo esc_url($banner->url); ?>" target="_blank"><?php echo esc_html($banner->url); ?></a></td>
                                <td><?php echo esc_html($banner->position); ?></td>
                                <td><?php echo intval($banner->views); ?></td>
                                <td><?php echo esc_html($banner->init_date); ?></td>
                                <td><?php echo esc_html($banner->end_date); ?></td>
                                <td>
                                    <?php if ($banner->path_mobile) : ?>
                                        <img src="<?php echo esc_url($banner->path_mobile); ?>" alt="BannerMobile" class="img-thumbnail" style="max-width: 100px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($banner->path_desktop) : ?>
                                        <img src="<?php echo esc_url($banner->path_desktop); ?>" alt="BannerDesktop" class="img-thumbnail" style="max-width: 100px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="checkbox" <?php checked($banner->active, 1); ?> onclick="toggleBannerActive(<?php echo intval($banner->ID); ?>)">
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                // Mostrar paginación
                $pagination_args = array(
                    'total' => $total_pages,
                    'current' => $current_page,
                    'format' => '?paged=%#%',
                    'prev_text' => __('&laquo; Anterior'),
                    'next_text' => __('Siguiente &raquo;'),
                );
                echo paginate_links($pagination_args);
                ?>
            </div>
        </div>
    </div>
    <script>
        function previewImageDesktop(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('image-preview-desktop');
                output.innerHTML = '<img src="' + reader.result + '" alt="Banner" class="img-thumbnail" style="max-width: 300px;">';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function previewImageMobile(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('image-preview-mobile');
                output.innerHTML = '<img src="' + reader.result + '" alt="Banner" class="img-thumbnail" style="max-width: 300px;">';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function clearImagePreview() {
            document.getElementById('image-preview-desktop').innerHTML = '';
            document.getElementById('image-preview-mobile').innerHTML = '';
        }

        function resetForm() {
            document.getElementById('banner-form').reset();
        }

        function toggleBannerActive(bannerId) {
            // Aquí puedes agregar la lógica para activar/desactivar el banner
            // Por ejemplo, usando AJAX para actualizar el estado en la base de datos
        }
    </script>
    <?php
}
?>
