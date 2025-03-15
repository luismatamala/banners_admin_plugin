<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'custom_banner_plugin_menu');

function custom_banner_plugin_menu() {
    add_menu_page(
        'Banners',
        'Banners',
        'manage_options',
        'custom_banner',
        'custom_banner_page',
        'dashicons-format-image',
        20
    );

}

require_once plugin_dir_path(__FILE__) . 'pages/banner.php';
