<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}



function basicrum_callback_section_general(){
    // We can put some description of the section here if we need it.
}

function basicrum_callback_section_monitor_types(){
    // We can put some description of the section here if we need it.
}

// Start adding fields and elements we need for settings of BasicRUM

function basicrum_callback_field_number( $args ) {

    $options = get_option( 'basicrum_options', basicrum_options_default() );

    $id = isset( $args['id'] ) ? $args['id'] : '';
    $label = isset( $args['label'] ) ? $args['label'] : '';

    $value = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';

    echo '<input id="basicrum_options_'. $id .'" name="basicrum_options['. $id .']" type="number" size="5" value="'. $value .'"><br>';
    echo '<label for="basicrum_options_'. $id .'">'. $label .'</label>';
    
}

function basicrum_callback_field_text( $args ) {
    $options = get_option( 'basicrum_options', basicrum_options_default() );

    $id = isset( $args['id'] ) ? $args['id'] : '';
    $label = isset( $args['label'] ) ? $args['label'] : '';

    $value = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';

    echo '<input id="basicrum_options_'. $id .'" name="basicrum_options['. $id .']" type="text" size="40" value="'. $value .'"><br>';
    echo '<label for="basicrum_options_'. $id .'">'. $label .'</label>';
    
}

function basicrum_callback_monitoring( $args ) {
    $options = get_option( 'basicrum_options', basicrum_options_default() );

    $id = isset( $args['id'] ) ? $args['id'] : '';
    $label = isset( $args['label'] ) ? $args['label'] : '';

    $selected_option = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';

    $radio_options = array(

        '1.737.20' => esc_html__('boomerang-1.737.20', 'basicrum'),
        '1.737.40' => 'boomerang-1.737.40',
        '1.737.60' => 'boomerang-1.737.60'

    );

    foreach ( $radio_options as $value => $label ){

        $checked = checked( $selected_option === $value, true, false );
        echo '<label><input name="basicrum_options['. $id .']" type="radio" value="'. $value .'"'. $checked .'> ';
        echo '<span>'. $label .'</span></label><br>';

    }
   
}

function basicrum_callback_script_placement( $args ) {
    $options = get_option( 'basicrum_options', basicrum_options_default() );

    $id = isset( $args['id'] ) ? $args['id'] : '';
    $label = isset( $args['label'] ) ? $args['label'] : '';

    $selected_options = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';

    $radio_options = array(

        'wp_head' => esc_html__('Insert script in header', 'basicrum'),
        'wp_footer' => esc_html__('Insert script in the footer', 'basicrum')
        

    );

    foreach ( $radio_options as $value => $label ){

        $checked = checked( $selected_options === $value, true, false );
        echo '<label><input name="basicrum_options['. $id .']" type="radio" value="'. $value .'"'. $checked .'> ';
        echo '<span>'. $label .'</span></label><br>';

    }
}