<?php
if ( ! defined('ABSPATH') ){
    exit;
}

function basicrum_register_settings() {

    register_setting(
        'basicrum_options',
        'basicrum_options',
        'basicrum_callback_validate_options'
    );

    add_settings_section(
        'basicrum_section_general',
        esc_html__('General Settings', 'basicrum'),
        'basicrum_callback_section_general',       
        'basicrum'
    );

    add_settings_section(
        'basicrum_section_monitor_types',
        esc_html__('What would you like to monitor?','basicrum'),
        'basicrum_callback_section_monitor_types',
        'basicrum'
    );

    // Add text input field for custom URL, where data from Boomerang script should be send

    add_settings_field(
        'url_to_send_data',
        esc_html__('Send Data to:', 'basicrum'),
        'basicrum_callback_field_text',
        'basicrum',
        'basicrum_section_general',
        ['id' => 'url_to_send_data', 'label' => esc_html__('Set the URL, where data from Boomerang script should be send', 'basicrum')]
    );

    // Add number input field for delay in milliseconds

    add_settings_field(
        'delay_sending_data',
        esc_html__('Delay Beacon Sending:', 'basicrum'),
        'basicrum_callback_field_number',
        'basicrum',
        'basicrum_section_general',
        ['id' => 'delay_sending_data', 'label' => esc_html__('Set delay in milliseconds (For example 5000)', 'basicrum')]
    );
    // Add selection for script insert position

    add_settings_field(
        'script_position',
        esc_html__('Script Insert Options:', 'basicrum'),
        'basicrum_callback_script_placement',
        'basicrum',
        'basicrum_section_general',
        ['id' => 'script_position', 'label' => esc_html__('Select where to insert script.', 'basicrum')]
    );

    // Add radio field for monitoring type

    add_settings_field(
        'monitoring_type',
        esc_html__('What do you want to monitor:', 'basicrum'),
        'basicrum_callback_monitoring',
        'basicrum',
        'basicrum_section_monitor_types',
        ['id' => 'monitoring_type', 'label' => esc_html__('Select options', 'basicrum')]
    );

}
add_action('admin_init', 'basicrum_register_settings');