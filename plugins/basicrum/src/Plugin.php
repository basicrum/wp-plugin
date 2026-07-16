<?php
/**
 * Main plugin orchestrator.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class - registers all services and hooks.
 */
class Plugin {

	/**
	 * Register all plugin services and hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Services loaded on every request (frontend + admin).
		new Setup();
		new Compatibility();

		if ( is_admin() ) {
			$this->register_admin_services();
		}

		// Frontend asset injection (not in admin).
		if ( ! is_admin() ) {
			new Assets();
		}
	}

	/**
	 * Register admin-only services.
	 *
	 * @return void
	 */
	private function register_admin_services() {
		new Admin\Settings\Page();
		new Admin\Upgrades();
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'basicrum',
			false,
			dirname( plugin_basename( BASICRUM_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
