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
		$settings = get_option( Helpers::OPTION_KEY, false );

		// Save default options if they don't exist yet.
		if ( false === $settings ) {
			update_option( Helpers::OPTION_KEY, Helpers::get_defaults() );
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
