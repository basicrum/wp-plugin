<?php
/**
 * Activation, deactivation, and lifecycle hooks.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup class - handles plugin activation and deactivation.
 */
class Setup {

	/**
	 * Constructor - register lifecycle hooks.
	 */
	public function __construct() {
		register_activation_hook( BASICRUM_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( BASICRUM_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$settings       = get_option( Helpers::OPTION_KEY, false );
		$stored_version = get_option( Helpers::VERSION_KEY, false );

		// Save default options if they don't exist yet.
		if ( false === $settings ) {
			update_option( Helpers::OPTION_KEY, Helpers::get_defaults() );
		}

		// A versionless legacy installation still needs the 1.0.1 migration.
		// Only mark a genuinely new installation as current.
		$is_new_install = false === $settings
			&& false === $stored_version
			&& false === get_option( 'basicrum_options', false );

		if ( $is_new_install ) {
			update_option( Helpers::VERSION_KEY, BASICRUM_VERSION );
		}
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Clean up transients.
		delete_transient( 'basicrum_notices' );
	}
}
