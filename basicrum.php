<?php
/**
 * Plugin Name:       Basic Rum Wordpress Plugin
 * Plugin URI:        https://www.basicrum.com/
 * Description:       Budget - Open Source - Real User Monitoring system.
 * Version:           1.0.0
 * Author:            Tsvetan Stoychev
 * Author URI:        https://www.basicrum.com/contact/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       basicrum
 * Domain Path:       /languages
 */

 // Exit if file is called directly. Bye bye, Captain!
 
 if ( ! defined( 'ABSPATH' ) ){
    exit;
 }

// We can speak many languages

function basicrum_load_textdomain() {
    load_plugin_textdomain('basicrum', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
}

add_action( 'init', 'basicrum_load_textdomain');

// Include dependencies just if it is admin panel. Aye aye?

if ( is_admin() ){
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-callbacks.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-register.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-validate.php';
}

require_once plugin_dir_path( __FILE__ ) . 'includes/core-functions.php';

// Plugin default settings. These settings are lodaded once the plugin is activated!
function basicrum_options_default() {

    return array(
        'url_to_send_data' => 'https://beacon.basicrum.com/beacon/catcher.php',
        'delay_sending_data' => 5000,
        'script_position' => 'wp_head',
        'monitoring_type' => '1.737.60',
    );

}