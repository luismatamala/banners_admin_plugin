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
                        $country = sanitize_text_field($_POST['country']);

                        if (!function_exists('wp_handle_upload')) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                        }

                        $uploadedfile_desktop = $_FILES['banner_image_desktop'];
                        $uploadedfile_mobile = $_FILES['banner_image_mobile'];
                        $upload_overrides = array('test_form' => false);
                        $movefile_desktop = wp_handle_upload($uploadedfile_desktop, $upload_overrides);
                        $movefile_mobile = wp_handle_upload($uploadedfile_mobile, $upload_overrides);

                        if (isset($movefile_desktop['type']) && strpos($movefile_desktop['type'], 'image') !== false && 
                            isset($movefile_mobile['type']) && strpos($movefile_mobile['type'], 'image') !== false) {

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
                                    'country' => $country,
                                    'init_date' => $banner_start_date ? $banner_start_date : null,
                                    'end_date' => $banner_end_date ? $banner_end_date : null,
                                    'active' => $banner_active
                                )
                            );

                            echo '<div class="alert alert-success">Datos guardados con éxito.</div>';
                        } else {
                            echo '<div class="alert alert-danger">Error al subir las imágenes. Asegúrese de que son archivos de imagen válidos.</div>';
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
                                    <label for="banner_description" class="form-label">Descripción</label>
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
                                <div class="mb-3">
                                    <label for="country" class="form-label">País *</label>
                                    <select name="country" id="country" class="form-select" required>
                                        <option value="CL">Chile</option>
                                        <option value="AR">Argentina</option>
                                        <option value="PE">Perú</option>
                                        <option value="CO">Colombia</option>
                                        <option value="UY">Uruguay</option>
                                        <option value="PY">Paraguay</option>
                                        <option value="BR">Brasil</option>
                                        <option value="EC">Ecuador</option>
                                    </select>
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
                <table id="banners-table" class="table table-bordered table-responsive table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>URL</th>
                            <th>Posición</th>
                            <th>Vistas</th>
                            <th>Vistas pend.</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>País</th>
                            <th>Banner mob.</th>
                            <th>Banner desk.</th>
                            <th>Editar</th>
                            <!-- <th>Activo</th>
                            <th>Eliminar</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener los banners guardados
                        $banners = $wpdb->get_results("SELECT * FROM $table_name LIMIT $offset, $items_per_page");
                        foreach ($banners as $banner) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($banner->name); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($banner->url); ?>" target="_blank" title="<?php echo esc_attr($banner->url); ?>" class="table-url">
                                        <?php echo esc_html($banner->url); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($banner->position); ?></td>
                                <td><?php echo intval($banner->views); ?></td>
                                <td><?php echo intval($banner->remaining_views); ?></td>
                                <td>
                                    <?php echo !empty($banner->init_date) ? date('d-m-Y', strtotime($banner->init_date)) : ''; ?>
                                </td>
                                <td>
                                    <?php echo !empty($banner->end_date) ? date('d-m-Y', strtotime($banner->end_date)) : ''; ?>
                                </td>
                                <td><?php echo esc_html($banner->country); ?></td>
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
                                    <button class="btn btn-success edit-banner" data-id="<?php echo intval($banner->ID); ?>">Editar</button>
                                </td>
                                <!--<td>
                                <input type="checkbox" <?php checked($banner->active, 1); ?> onclick="toggleBannerActive(<?php echo intval($banner->ID); ?>, <?php echo intval(!$banner->active); ?>)">
                                </td>
                                <td>
                                    <button class="btn btn-danger delete-banner" data-id="<?php echo intval($banner->ID); ?>">X</button>
                                </td> -->
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
                    'type' => 'list', // Esto mejora el formato de la paginación
                );
                echo '<div class="pagination">' . paginate_links($pagination_args) . '</div>';
                ?>
            </div>
        </div>
    </div>

    <!-- Modal para editar banner -->
    <!-- Modal para editar banner -->
    <div class="modal fade" id="editBannerModal" tabindex="-1" aria-labelledby="editBannerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBannerModalLabel">Editar Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-banner-form" enctype="multipart/form-data">
                        <input type="hidden" name="banner_id" id="edit-banner-id">
                        
                        <div class="mb-3">
                            <label for="edit-banner-name" class="form-label">Nombre</label>
                            <input type="text" name="banner_name" id="edit-banner-name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-url" class="form-label">URL</label>
                            <input type="text" name="banner_url" id="edit-banner-url" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-position" class="form-label">Posición</label>
                            <select name="banner_position" id="edit-banner-position" class="form-select" required>
                                <option value="top">Arriba</option>
                                <option value="bottom">Abajo</option>
                                <option value="sidebar">Barra Lateral</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-views" class="form-label">Vistas</label>
                            <input type="number" name="banner_views" id="edit-banner-views" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-start-date" class="form-label">Fecha Inicio</label>
                            <input type="date" name="banner_start_date" id="edit-banner-start-date" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-end-date" class="form-label">Fecha Fin</label>
                            <input type="date" name="banner_end_date" id="edit-banner-end-date" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-country" class="form-label">País</label>
                            <select name="country" id="edit-banner-country" class="form-select" required>
                                <option value="CL">Chile</option>
                                <option value="AR">Argentina</option>
                                <option value="PE">Perú</option>
                                <option value="CO">Colombia</option>
                                <option value="UY">Uruguay</option>
                                <option value="PY">Paraguay</option>
                                <option value="BR">Brasil</option>
                                <option value="EC">Ecuador</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-banner-mobile" class="form-label">Banner Mobile</label>
                            <input type="file" name="banner_image_mobile" id="edit-banner-mobile" class="form-control">
                            <div id="edit-banner-mobile-preview" class="mt-2"></div> <!-- Contenedor para la imagen actual -->
                        </div>

                        <div class="mb-3">
                            <label for="edit-banner-desktop" class="form-label">Banner Desktop</label>
                            <input type="file" name="banner_image_desktop" id="edit-banner-desktop" class="form-control">
                            <div id="edit-banner-desktop-preview" class="mt-2"></div> <!-- Contenedor para la imagen actual -->
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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

        function toggleBannerActive(bannerId, checked) {
            console.log("checked", checked);
            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                type: 'POST',
                data: {
                    action: 'toggle_banner_active',
                    banner_id: bannerId,
                    checked: checked
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }

        jQuery(document).ready(function($){
            $('#banners-table').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 5
            });

            $('.delete-banner').click(function(){
                var bannerId = $(this).data('id');
                if (confirm('¿Estás seguro de que quieres eliminar este banner?')) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'delete_banner',
                            banner_id: bannerId
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        }
                    });
                }
            });
        });

        // Abrir el modal de edición con los datos del banner
        $('.edit-banner').click(function () {
            var bannerId = $(this).data('id');

            // Obtener los datos del banner desde el servidor
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_banner',
                    banner_id: bannerId
                },
                success: function (response) {
                    if (response.success) {
                        // Llenar el formulario del modal con los datos del banner
                        $('#edit-banner-id').val(response.data.ID);
                        $('#edit-banner-name').val(response.data.name);
                        $('#edit-banner-url').val(response.data.url);
                        $('#edit-banner-position').val(response.data.position);
                        $('#edit-banner-views').val(response.data.views);
                        $('#edit-banner-start-date').val(response.data.init_date);
                        $('#edit-banner-end-date').val(response.data.end_date);
                        $('#edit-banner-country').val(response.data.country);

                        // Mostrar las imágenes actuales
                        if (response.data.path_mobile) {
                            $('#edit-banner-mobile-preview').html(
                                '<img src="' + response.data.path_mobile + '" alt="Banner Mobile" class="img-thumbnail" style="max-width: 150px;">'
                            );
                        } else {
                            $('#edit-banner-mobile-preview').html('<p>No hay imagen móvil.</p>');
                        }

                        if (response.data.path_desktop) {
                            $('#edit-banner-desktop-preview').html(
                                '<img src="' + response.data.path_desktop + '" alt="Banner Desktop" class="img-thumbnail" style="max-width: 150px;">'
                            );
                        } else {
                            $('#edit-banner-desktop-preview').html('<p>No hay imagen de escritorio.</p>');
                        }

                        // Mostrar el modal
                        $('#editBannerModal').modal('show');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        });

        // Enviar los cambios al servidor
        $('#edit-banner-form').submit(function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'update_banner'); // Agregar el action aquí

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Recargar la página para reflejar los cambios
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        });
    </script>
<?php
}
?>
