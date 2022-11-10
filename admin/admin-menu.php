<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}

function basicrum_add_menu_item() {

    add_menu_page(
        esc_html__('BasicRUM Config', 'basicrum'),
        esc_html__('BasicRUM', 'basicrum'),
        'manage_options',
        'basicrum',
        'basicrum_display_settings_page',
        'dashicons-analytics',
        null
    );
}
add_action( 'admin_menu', 'basicrum_add_menu_item' );