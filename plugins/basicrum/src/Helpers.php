<?php
/**
 * Static utility helpers.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers class - settings retrieval and utility methods.
 */
class Helpers {

	/**
	 * Option key used in wp_options table.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'basicrum_settings';

	/**
	 * Version option key.
	 *
	 * @var string
	 */
	const VERSION_KEY = 'basicrum_version';

	/**
	 * Get plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( self::OPTION_KEY, array() );

		// Preserve values saved under the setting name used before 1.0.1.
		if ( ! array_key_exists( 'brum_site_id', $settings ) && isset( $settings['site_id'] ) ) {
			$settings['brum_site_id'] = $settings['site_id'];
		}

		unset( $settings['site_id'] );

		return wp_parse_args( $settings, self::get_defaults() );
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'enabled'                => '0',
			'beacon_url'             => '',
			'brum_site_id'           => '',
			'track_admins'           => '0',
			'consent_enabled'        => '0',
			'consent_mode'           => 'explicit',
			'wait_after_onload'      => '0',
			'delay_ms'               => 0,
			'script_position'        => 'footer',
			'use_unminified_loaders' => '0',
		);
	}

	/**
	 * Get the URL to the plugin's assets directory.
	 *
	 * @param string $path Optional relative path to append.
	 * @return string
	 */
	public static function get_asset_url( $path = '' ) {
		return plugins_url( 'assets/' . ltrim( $path, '/' ), BASICRUM_PLUGIN_FILE );
	}

	/**
	 * Get the filesystem path to the plugin's assets directory.
	 *
	 * @param string $path Optional relative path to append.
	 * @return string
	 */
	public static function get_asset_path( $path = '' ) {
		return BASICRUM_PLUGIN_DIR . 'assets/' . ltrim( $path, '/' );
	}

	/**
	 * Check if the plugin is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = self::get_settings();
		return ! empty( $settings['enabled'] ) && '1' === $settings['enabled'];
	}

	/**
	 * Check if consent mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_consent_enabled() {
		$settings = self::get_settings();
		return ! empty( $settings['consent_enabled'] ) && '1' === $settings['consent_enabled'];
	}

	/**
	 * Get the bundled Boomerang version string.
	 *
	 * @return string
	 */
	public static function get_boomerang_version() {
		return '1.815.60';
	}
}
