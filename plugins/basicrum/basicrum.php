<?php
/**
 * Plugin Name:       Basicrum - Real User Monitoring
 * Plugin URI:        https://www.basicrum.com/
 * Description:       Privacy-first Real User Monitoring with consent-controlled loading, page types, and Web Vitals.
 * Version:           1.0.2
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
define( 'BASICRUM_VERSION', '1.0.2' );
define( 'BASICRUM_PLUGIN_FILE', __FILE__ );
define( 'BASICRUM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Load the optimized Composer autoloader included in release packages.
$basicrum_autoloader = BASICRUM_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! is_readable( $basicrum_autoloader ) ) {
	$basicrum_missing_autoloader_notice = static function () {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Basicrum could not start because its Composer dependencies are missing. Reinstall the plugin from an official release ZIP.', 'basicrum' )
		);
	};

	add_action( 'admin_notices', $basicrum_missing_autoloader_notice );
	add_action( 'network_admin_notices', $basicrum_missing_autoloader_notice );
	return;
}

require_once $basicrum_autoloader;

// Bootstrap the plugin.
$basicrum_plugin = new \Basicrum\WP\Plugin();
$basicrum_plugin->register();
