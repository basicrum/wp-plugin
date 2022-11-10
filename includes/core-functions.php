<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}


function basicrum_code() {
// On plugin install, all values doesn't exist in db. That's why we do a check and get values from function basicrum_options_default()

    if ( !isset( get_option('basicrum_options')['url_to_send_data'] ) ) {
        $url = basicrum_options_default()['url_to_send_data'];
    } else {
        $url = get_option('basicrum_options')['url_to_send_data'];
    }
    
    if ( !isset( get_option('basicrum_options')['delay_sending_data'] ) ) {
        $milliseconds = basicrum_options_default()['delay_sending_data'];
    } else {
        $milliseconds = get_option('basicrum_options')['delay_sending_data'];
    }

    if ( !isset( get_option('basicrum_options')['monitoring_type'] ) ) {
        $boomerang_version = basicrum_options_default()['monitoring_type'];
    } else {
        $boomerang_version = get_option('basicrum_options')['monitoring_type'];
    }
    

    //echo '<script src="'.plugin_dir_url(__FILE__) . 'boomerang-'. $boomerang_version .'.cutting-edge.min.js">';
}

if ( !isset( get_option('basicrum_options')['script_position'] ) ) {
    $action = basicrum_options_default()['script_position'];
} else {
    $action = get_option('basicrum_options')['script_position'];
}

add_action( $action, 'basicrum_code');