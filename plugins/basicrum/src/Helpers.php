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
	 * Supported consent mode values.
	 *
	 * @var array
	 */
	const CONSENT_MODES = array( 'explicit', 'implicit', 'cookie_popup' );

	/**
	 * Settings required before monitoring can run.
	 *
	 * @var array
	 */
	const REQUIRED_SETTINGS = array( 'beacon_url', 'brum_site_id' );

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

		if ( isset( $settings['consent_mode'] ) ) {
			$settings['consent_mode'] = self::normalize_consent_mode( $settings['consent_mode'] );
		}

		return wp_parse_args( $settings, self::get_defaults() );
	}

	/**
	 * Normalize current and legacy consent mode values.
	 *
	 * @param string $consent_mode Stored consent mode.
	 * @return string Supported consent mode.
	 */
	public static function normalize_consent_mode( $consent_mode ) {
		if ( 'cookie_banner' === $consent_mode ) {
			return 'cookie_popup';
		}

		if ( ! in_array( $consent_mode, self::CONSENT_MODES, true ) ) {
			return 'explicit';
		}

		return $consent_mode;
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'enabled'                => '0',
			'development_mode'       => '0',
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
	 * Get the required settings that have not been populated.
	 *
	 * @param array $settings Plugin settings.
	 * @return array Missing setting keys.
	 */
	public static function get_missing_required_settings( $settings ) {
		$missing_settings = array();

		foreach ( self::REQUIRED_SETTINGS as $setting_key ) {
			if ( ! isset( $settings[ $setting_key ] ) || '' === trim( (string) $settings[ $setting_key ] ) ) {
				$missing_settings[] = $setting_key;
			}
		}

		return $missing_settings;
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
