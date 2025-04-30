<?php
/*
Plugin Name: Custom Banner Plugin
Description: Plugin admin banners WordPress.
Version: 1.0
Author: Luis Matamala
*/

// Evita accesos directos al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CUSTOM_BANNER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CUSTOM_BANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos del administrador
require_once CUSTOM_BANNER_PLUGIN_PATH . 'admin/admin-menu.php';

// Hook de activación del plugin
register_activation_hook(__FILE__, 'custom_banner_plugin_activate');

function custom_banner_enqueue_styles() { 
    wp_enqueue_style('custom-banner-styles', plugin_dir_url(__FILE__) . 'admin/css/styles.css'); 
}

add_action('admin_enqueue_scripts', 'custom_banner_enqueue_styles');

// Activación del plugin: crear la tabla
function custom_banner_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        ID INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        description VARCHAR(250) NULL,
        path_desktop VARCHAR(100) NOT NULL,
        path_mobile VARCHAR(100) NOT NULL,
        url VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        client VARCHAR(100) NULL,
        views INT DEFAULT 0,
        remaining_views INT DEFAULT 0,
        init_date DATETIME NULL,
        end_date DATETIME NULL,
        country varchar(10) NOT NULL,
        creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        active SMALLINT DEFAULT 1,
        PRIMARY KEY (ID)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Acción para manejar la eliminación de banners vía AJAX
add_action('wp_ajax_delete_banner', 'delete_banner_callback');

function delete_banner_callback() {
    global $wpdb;

    // Validación de permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
    }

    // Validación del ID del banner
    if (!isset($_POST['banner_id']) || !is_numeric($_POST['banner_id'])) {
        wp_send_json_error(array('message' => 'ID no válido.'));
    }

    $banner_id = intval($_POST['banner_id']);
    $table_name = $wpdb->prefix . 'banners';

    // Eliminación del banner
    $deleted = $wpdb->delete($table_name, array('ID' => $banner_id), array('%d'));

    if ($deleted !== false) {
        wp_send_json_success(array('message' => 'Banner eliminado.'));
    } else {
        wp_send_json_error(array('message' => 'Error al eliminar.'));
    }

    wp_die();
}

// Acción para manejar la eliminación de banners vía AJAX
add_action('wp_ajax_toggle_banner_active', 'toggle_banner_active_callback');

function toggle_banner_active_callback() {
    global $wpdb;

    // Validación de permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'No tienes permisos.'));
    }

    // Validación del ID del banner
    if (!isset($_POST['banner_id']) || !is_numeric($_POST['banner_id'])) {
        wp_send_json_error(array('message' => 'ID no válido.'));
    }

    $banner_id = intval($_POST['banner_id']);
    $checked = intval($_POST['checked']);

    $table_name = $wpdb->prefix . 'banners';

    // Eliminación del banner
    $updated = $wpdb->update(
        $table_name, 
        array('active' => $checked), // Valores a actualizar
        array('ID' => $banner_id),   // Condición WHERE
        array('%d'),                 // Formato de los valores a actualizar
        array('%d')                  // Formato de la condición WHERE
    );

    if ($updated !== false) {
        wp_send_json_success(array('message' => 'Estado del banner actualizado.'));
    } else {
        wp_send_json_error(array('message' => 'Error al actualizar el estado del banner.'));
    }
    wp_die(); // Finaliza la ejecución del script de AJAX
}

add_action('rest_api_init', function () {
    //http://local.jetsmart.com/wp-json/banners/v1/all
    register_rest_route('banners/v1', '/all', array(
        'methods' => 'GET',
        'callback' => 'get_all_banners',
        'permission_callback' => '__return_true'
    ));
    
    //http://local.jetsmart.com/wp-json/banners/v1/active
    register_rest_route('banners/v1', '/active', array(
        'methods' => 'GET',
        'callback' => 'get_active_banners',
        'permission_callback' => '__return_true'
    ));

    //http://local.jetsmart.com//wp-json/banners/v1/decrement-views/6
    register_rest_route('banners/v1', '/decrement-views/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'decrement_banner_views',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            )
        )
    ));

    //http://local.jetsmart.com/wp-json/banners/v1/get-banner?country=CL&position=top-banner
    register_rest_route('banners/v1', '/get-banner', array(
        'methods'  => 'GET',
        'callback' => 'get_banner_by_country_and_position',
        'permission_callback' => '__return_true',
        'args' => array(
            'country' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param);
                }
            ),
            'position' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param);
                }
            ),
            'banner_id' => array(
                'required' => false,
                'validate_callback' => function($param) {
                    return is_numeric($param) || is_null($param);
                }
            )
        )
    ));
});

function get_all_banners() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    
    if (empty($results)) {
        return new WP_Error('no_banners', 'No banners found', array('status' => 404));
    }
    
    return $results;
}

function get_active_banners() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    $current_date = current_time('mysql');
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE active = 1"
    ));
    
    if (empty($results)) {
        return new WP_Error('no_active_banners', 'No active banners found', array('status' => 404));
    }
    
    return $results;
}

function decrement_banner_views(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    $banner_id = $request->get_param('id');

    // Primero verificamos que el banner exista
    $banner = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE ID = %d", 
        $banner_id
    ));

    if (!$banner) {
        return new WP_Error('banner_not_found', 'Banner not found', array('status' => 404));
    }

    // Verificamos que aún tenga views disponibles
    if ($banner->remaining_views <= 0) {
        return new WP_Error('no_views_left', 'This banner has no remaining views', array('status' => 400));
    }

    // Decrementamos el valor de remaining_views
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE $table_name 
        SET remaining_views = remaining_views - 1 
        WHERE ID = %d AND remaining_views > 0",
        $banner_id
    ));

    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to update banner views', array('status' => 500));
    }

    // Obtenemos el nuevo valor para devolverlo en la respuesta
    $updated_views = $wpdb->get_var($wpdb->prepare(
        "SELECT remaining_views FROM $table_name WHERE ID = %d",
        $banner_id
    ));

    return array(
        'success' => true,
        'banner_id' => $banner_id,
        'remaining_views' => (int)$updated_views,
        'message' => 'Remaining views decremented successfully'
    );
}

function get_banner_by_country_and_position($request) {
    global $wpdb;

    $country    = $request->get_param('country');
    $position   = $request->get_param('position');
    $banner_id  = $request->get_param('banner_id');
    $table_name = $wpdb->prefix . 'banners';
    $current_date = current_time('Y-m-d');

    // Query base con filtros comunes
    $query_base = "FROM $table_name 
        WHERE active = 1
        AND remaining_views > 0
        AND country = %s
        AND position = %s
        AND (
            (init_date IS NULL AND end_date IS NULL)
            OR (init_date IS NOT NULL AND end_date IS NOT NULL AND %s BETWEEN DATE(init_date) AND DATE(end_date))
            OR (init_date IS NOT NULL AND end_date IS NULL AND DATE(init_date) <= %s)
            OR (init_date IS NULL AND end_date IS NOT NULL AND DATE(end_date) >= %s)
        )";

    // Parámetros comunes para prepare
    $params = [$country, $position, $current_date, $current_date, $current_date];

    try {
        if (empty($banner_id)) {
            // 🚀 Banner aleatorio
            $query = "SELECT * $query_base ORDER BY RAND() LIMIT 1";
            $results = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $banner_id = intval($banner_id);

            // 🔍 Buscar siguiente ID mayor
            $query_next = "SELECT * $query_base AND ID > %d ORDER BY ID ASC LIMIT 1";
            $results = $wpdb->get_results($wpdb->prepare($query_next, ...array_merge($params, [$banner_id])));

            // 🔁 Si no hay uno mayor, buscar el menor distinto
            if (empty($results)) {
                $query_fallback = "SELECT * $query_base AND ID != %d ORDER BY ID ASC LIMIT 1";
                $results = $wpdb->get_results($wpdb->prepare($query_fallback, ...array_merge($params, [$banner_id])));

                // 🟨 Si tampoco hay otro, mostrar el mismo (si existe)
                if (empty($results)) {
                    $query_same = "SELECT * $query_base AND ID = %d LIMIT 1";
                    $results = $wpdb->get_results($wpdb->prepare($query_same, ...array_merge($params, [$banner_id])));
                }
            }
        }

        if (empty($results)) {
            return new WP_Error('no_active_banners', 'No active banners found for given parameters', array('status' => 404));
        }

        return rest_ensure_response($results[0]); // solo uno

    } catch (Exception $e) {
        error_log('🔴 Error en get_banner_by_country_and_position: ' . $e->getMessage());
        return new WP_Error('server_error', 'Ocurrió un error inesperado', array('status' => 500));
    }
}

// Obtener datos del banner
add_action('wp_ajax_get_banner', 'get_banner_callback');
function get_banner_callback() {
    global $wpdb;

    $banner_id = intval($_POST['banner_id']);
    $table_name = $wpdb->prefix . 'banners';

    $banner = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $banner_id));

    if ($banner) {
        wp_send_json_success(array(
            'ID' => $banner->ID,
            'name' => $banner->name,
            'url' => $banner->url,
            'position' => $banner->position,
            'views' => $banner->views,
            'init_date' => $banner->init_date ? date('Y-m-d', strtotime($banner->init_date)) : '',
            'end_date' => $banner->end_date ? date('Y-m-d', strtotime($banner->end_date)) : '',
            'country' => $banner->country,
            'path_mobile' => $banner->path_mobile,
            'path_desktop' => $banner->path_desktop,
            'active' => $banner->active
        ));
    } else {
        wp_send_json_error(array('message' => 'Banner no encontrado.'));
    }
}

// Actualizar datos del banner
add_action('wp_ajax_update_banner', 'update_banner_callback');

function update_banner_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';

    $banner_id = intval($_POST['banner_id']);
    if (!$banner_id) {
        wp_send_json_error(array('message' => 'ID de banner no válido.'));
    }

    // Cargar funciones de manejo de archivos si es necesario
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $upload_overrides = array('test_form' => false);
    $banner_mobile = '';
    $banner_desktop = '';

    // Procesar imagen móvil si se cargó
    if (isset($_FILES['banner_image_mobile']) && !empty($_FILES['banner_image_mobile']['name'])) {
        $uploaded_mobile = $_FILES['banner_image_mobile'];
        $movefile_mobile = wp_handle_upload($uploaded_mobile, $upload_overrides);

        if (isset($movefile_mobile['type']) && strpos($movefile_mobile['type'], 'image') !== false) {
            $banner_mobile = $movefile_mobile['url'];
        } else {
            wp_send_json_error(array('message' => 'Error al subir imagen móvil.'));
        }
    }

    // Procesar imagen desktop si se cargó
    if (isset($_FILES['banner_image_desktop']) && !empty($_FILES['banner_image_desktop']['name'])) {
        $uploaded_desktop = $_FILES['banner_image_desktop'];
        $movefile_desktop = wp_handle_upload($uploaded_desktop, $upload_overrides);

        if (isset($movefile_desktop['type']) && strpos($movefile_desktop['type'], 'image') !== false) {
            $banner_desktop = $movefile_desktop['url'];
        } else {
            wp_send_json_error(array('message' => 'Error al subir imagen desktop.'));
        }
    }

    // Capturar el estado del checkbox activo
    $active = isset($_POST['banner_active']) ? 1 : 0;

    // Preparar datos base
    $data = array(
        'name' => sanitize_text_field($_POST['banner_name']),
        'url' => esc_url_raw($_POST['banner_url']),
        'position' => sanitize_text_field($_POST['banner_position']),
        'views' => intval($_POST['banner_views']),
        'remaining_views' => intval($_POST['banner_remaining_views']),
        'init_date' => !empty($_POST['banner_start_date']) ? sanitize_text_field($_POST['banner_start_date']) : null,
        'end_date' => !empty($_POST['banner_end_date'])   ? sanitize_text_field($_POST['banner_end_date'])   : null,
        'country' => sanitize_text_field($_POST['country']),
        'active' => $active
    );

    $formats = array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d');

    // Incluir imágenes solo si se subieron
    if (!empty($banner_mobile)) {
        $data['path_mobile'] = $banner_mobile;
        $formats[] = '%s';
    }

    if (!empty($banner_desktop)) {
        $data['path_desktop'] = $banner_desktop;
        $formats[] = '%s';
    }

    $updated = $wpdb->update(
        $table_name,
        $data,
        array('ID' => $banner_id),
        $formats,
        array('%d')
    );

    if ($updated !== false) {
        wp_send_json_success(array('message' => 'Banner actualizado correctamente.'));
    } else {
        wp_send_json_error(array('message' => 'No se pudo actualizar el banner o no hubo cambios.'));
    }
}

add_action('wp_ajax_update_banner_active_state', 'update_banner_active_state_callback');

function update_banner_active_state_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';

    $banner_id = intval($_POST['banner_id']);
    $active = intval($_POST['active']);

    if (!$banner_id) {
        wp_send_json_error(array('message' => 'ID inválido.'));
    }

    $updated = $wpdb->update(
        $table_name,
        array('active' => $active),
        array('ID' => $banner_id)
    );

    if ($updated !== false) {
        wp_send_json_success(array('message' => 'Estado actualizado correctamente.'));
    } else {
        wp_send_json_error(array('message' => 'No se pudo actualizar el estado.'));
    }
}