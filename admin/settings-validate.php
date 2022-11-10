<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}

// We do some face control here! Aye aye captain!

function basicrum_callback_validate_options( $input ){
    if (isset( $input['url_to_send_data'] ) ){
        $input['url_to_send_data'] = esc_url( $input['url_to_send_data'] );
    }

    $radio_options = array(
        '1.737.20' => '1.737.20',
        '1.737.40' => '1.737.40',
        '1.737.60' => '1.737.60'
    );

    if ( ! isset( $input['monitoring_type'], $radio_options ) ) {
        $input['monitoring_type'] = null;
    }

    if (! array_key_exists( $input['monitoring_type'], $radio_options ) ) {
        $input['monitoring_type'] = null;
    } 

    $radio_options = array(
        'wp_head' => 'Insert script in header',
        'wp_footer' => 'Insert script in the footer'
    );

    if ( ! isset( $input['script_position'], $radio_options ) ) {
        $input['script_position'] = null;
    }
    
    if (! array_key_exists( $input['script_position'], $radio_options ) ) {
        $input['script_position'] = null;
    } 

    return $input;
}