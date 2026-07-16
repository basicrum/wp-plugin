<?php
/**
 * Plugin Name:       Basicrum - Real User Monitoring
 * Plugin URI:        https://www.basicrum.com/
 * Description:       Budget, open source, Real User Monitoring powered by Boomerang.js. Track page load performance, page types, and Web Vitals.
 * Version:           1.0.1
 * Author:            Tsvetan Stoychev
 * Author URI:        https://www.basicrum.com/contact/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       basicrum
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'BASICRUM_VERSION', '1.0.1' );
define( 'BASICRUM_PLUGIN_FILE', __FILE__ );
define( 'BASICRUM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( BASICRUM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once BASICRUM_PLUGIN_DIR . 'vendor/autoload.php';
}

// Bootstrap the plugin.
$basicrum_plugin = new \Basicrum\WP\Plugin();
$basicrum_plugin->register();
