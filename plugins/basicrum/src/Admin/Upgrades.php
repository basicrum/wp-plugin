<?php
/**
 * Version-based database migrations.
 *
 * @package Basicrum
 */

namespace Basicrum\WP\Admin;

use Basicrum\WP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrades class - runs sequential migrations when plugin version changes.
 *
 * Pattern from wordpress-plausible/src/Admin/Upgrades.php.
 */
class Upgrades {

	/**
	 * Constructor - hook into init to check for pending upgrades.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_upgrade' ), 5 );
	}

	/**
	 * Check stored version against current and run migrations.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		$stored_version = get_option( Helpers::VERSION_KEY, '0.0.0' );

		if ( version_compare( $stored_version, BASICRUM_VERSION, '>=' ) ) {
			return;
		}

		// Migration: convert old PoC option key to new key.
		if ( version_compare( $stored_version, '1.0.1', '<' ) ) {
			$this->upgrade_to_101();
		}

		// Store the new version after all migrations.
		update_option( Helpers::VERSION_KEY, BASICRUM_VERSION );
	}

	/**
	 * Migrate from PoC option key (basicrum_options) to new key (basicrum_settings).
	 *
	 * Maps the old field names to the new structure.
	 *
	 * @return void
	 */
	private function upgrade_to_101() {
		$old_options = get_option( 'basicrum_options', false );

		if ( false === $old_options || ! is_array( $old_options ) ) {
			return;
		}

		$defaults    = Helpers::get_defaults();
		$new_options = $defaults;

		// Map old keys to new keys.
		if ( ! empty( $old_options['url_to_send_data'] ) ) {
			$new_options['beacon_url'] = esc_url_raw( $old_options['url_to_send_data'] );
		}

		if ( isset( $old_options['delay_sending_data'] ) ) {
			$new_options['delay_ms'] = absint( $old_options['delay_sending_data'] );
		}

		if ( ! empty( $old_options['script_position'] ) ) {
			// Old values were 'wp_head' / 'wp_footer'; new values are 'header' / 'footer'.
			$position_map = array(
				'wp_head'   => 'header',
				'wp_footer' => 'footer',
			);
			if ( isset( $position_map[ $old_options['script_position'] ] ) ) {
				$new_options['script_position'] = $position_map[ $old_options['script_position'] ];
			}
		}

		// Enable the plugin since it was implicitly enabled in the PoC.
		$new_options['enabled'] = '1';

		// Save the new options.
		update_option( Helpers::OPTION_KEY, $new_options );

		// Remove the old option.
		delete_option( 'basicrum_options' );
	}
}
