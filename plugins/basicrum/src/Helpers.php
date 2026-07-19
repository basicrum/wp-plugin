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
	 * Settings required before monitoring can run.
	 *
	 * @var array
	 */
	const REQUIRED_SETTINGS = array( 'beacon_url', 'brum_site_id' );

	/**
	 * Settings stored as canonical '1' or '0' strings.
	 *
	 * @var array
	 */
	const BOOLEAN_SETTINGS = array(
		'enabled',
		'development_mode',
		'track_admins',
		'consent_enabled',
		'strip_query_string',
		'wait_after_onload',
		'use_unminified_loaders',
	);

	/**
	 * Get plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$defaults = self::get_defaults();
		$settings = wp_parse_args( $settings, $defaults );

		foreach ( self::BOOLEAN_SETTINGS as $setting_key ) {
			$settings[ $setting_key ] = self::normalize_boolean_setting( $settings[ $setting_key ], $defaults[ $setting_key ] );
		}

		return $settings;
	}

	/**
	 * Normalize a stored boolean-like value without accepting arbitrary truthy
	 * input from programmatic option writes.
	 *
	 * @param mixed  $value         Stored setting value.
	 * @param string $default_value Canonical fallback value.
	 * @return string Canonical '1' or '0' value.
	 */
	private static function normalize_boolean_setting( $value, $default_value ) {
		if ( '1' === $value || 1 === $value || true === $value ) {
			return '1';
		}

		if ( '0' === $value || 0 === $value || false === $value ) {
			return '0';
		}

		return $default_value;
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
			'consent_enabled'        => '1',
			'consent_integration'    => ConsentIntegration::MODE_AUTOMATIC,
			'strip_query_string'     => '0',
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
	 * Check if consent-controlled loading is enabled.
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
