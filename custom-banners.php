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

function custom_banner_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'banners';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        ID INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        description VARCHAR(250) NULL,
        path VARCHAR(100) NOT NULL,
        url VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        client VARCHAR(100) NULL,
        views INT DEFAULT 0,
        remaining_views INT DEFAULT 0,
        init_date DATETIME NULL,
        end_date DATETIME NULL,
        creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        active SMALLINT DEFAULT 1,
        PRIMARY KEY (ID)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
