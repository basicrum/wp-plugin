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
		// Save default options if they don't exist yet.
		if ( false === get_option( Helpers::OPTION_KEY ) ) {
			update_option( Helpers::OPTION_KEY, Helpers::get_defaults() );
		}

		// Store the current plugin version.
		update_option( Helpers::VERSION_KEY, BASICRUM_VERSION );
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
