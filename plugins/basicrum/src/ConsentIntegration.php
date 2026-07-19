<?php
/**
 * Automatic consent-tool integration detection.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects supported consent APIs and selects one packaged adapter.
 */
class ConsentIntegration {

	/**
	 * Automatic integration setting value.
	 *
	 * @var string
	 */
	const MODE_AUTOMATIC = 'automatic';

	/**
	 * Manual integration setting value.
	 *
	 * @var string
	 */
	const MODE_MANUAL = 'manual';

	/**
	 * WP Consent API integration identifier.
	 *
	 * @var string
	 */
	const WP_CONSENT_API = 'wp-consent-api';

	/**
	 * Borlabs Cookie integration identifier.
	 *
	 * @var string
	 */
	const BORLABS_COOKIE = 'borlabs-cookie-v3';

	/**
	 * CookieYes integration identifier.
	 *
	 * @var string
	 */
	const COOKIEYES = 'cookieyes';

	/**
	 * Register Basicrum as a WP Consent API-aware plugin.
	 */
	public function __construct() {
		$plugin_basename = plugin_basename( BASICRUM_PLUGIN_FILE );

		add_filter( 'wp_consent_api_registered_' . $plugin_basename, '__return_true' );
	}

	/**
	 * Detect supported consent integrations that are active on this site.
	 *
	 * @return array Integration identifiers.
	 */
	public static function get_detected_integrations() {
		$detected = array();

		if ( static::runtime_class_exists( '\\WP_CONSENT_API' ) || static::runtime_function_exists( 'wp_has_consent' ) ) {
			$detected[] = self::WP_CONSENT_API;
		}

		// Public PHP API marker introduced in Borlabs Cookie 3.2.
		if ( static::runtime_function_exists( 'borlabsCookieApi' ) ) {
			$detected[] = self::BORLABS_COOKIE;
		}

		// Modern CookieYes runtime. The legacy class uses an incompatible JS API.
		if ( static::runtime_class_exists( '\\CookieYes\\Lite\\Includes\\CLI' ) ) {
			$detected[] = self::COOKIEYES;
		}

		/**
		 * Filter the consent integrations detected by Basicrum.
		 *
		 * This supports non-standard plugin bootstraps and controlled testing. Each
		 * value must be one of the ConsentIntegration identifier constants.
		 *
		 * @param array $detected Detected integration identifiers.
		 */
		$detected = apply_filters( 'basicrum_detected_consent_integrations', $detected );

		return self::normalize_detected_integrations( $detected );
	}

	/**
	 * Check whether a runtime class is available.
	 *
	 * This narrow boundary keeps provider marker behavior testable without
	 * defining third-party classes in the PHPUnit process.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return bool Whether the class is available.
	 */
	protected static function runtime_class_exists( $class_name ) {
		return class_exists( $class_name );
	}

	/**
	 * Check whether a runtime function is available.
	 *
	 * @param string $function_name Function name.
	 * @return bool Whether the function is available.
	 */
	protected static function runtime_function_exists( $function_name ) {
		return function_exists( $function_name );
	}

	/**
	 * Normalize a filtered detection result without trusting third-party input.
	 *
	 * @param mixed $detected Filtered integration identifiers.
	 * @return array Supported, unique integration identifiers.
	 */
	private static function normalize_detected_integrations( $detected ) {
		if ( ! is_array( $detected ) ) {
			return array();
		}

		return array_values( array_unique( array_intersect( $detected, self::get_supported_integrations() ) ) );
	}

	/**
	 * Select the automatic adapter.
	 *
	 * WP Consent API is the shared contract and takes precedence over direct
	 * provider adapters. Without it, Basicrum selects a direct adapter only when
	 * exactly one supported provider is detected. Ambiguous combinations fail
	 * closed and require manual integration.
	 *
	 * @return string|null Integration identifier, or null when none is safe.
	 */
	public static function get_automatic_integration() {
		$detected = self::get_detected_integrations();

		if ( in_array( self::WP_CONSENT_API, $detected, true ) ) {
			$selected = self::WP_CONSENT_API;
		} else {
			$direct_integrations = array_values(
				array_intersect(
					$detected,
					array( self::BORLABS_COOKIE, self::COOKIEYES )
				)
			);
			$selected            = 1 === count( $direct_integrations ) ? $direct_integrations[0] : null;
		}

		/**
		 * Filter the automatic consent integration selected by Basicrum.
		 *
		 * Return null to disable automatic adapter loading. Unsupported values are
		 * rejected so this filter cannot enqueue an arbitrary asset path.
		 *
		 * @param string|null $selected Selected integration identifier.
		 * @param array       $detected Detected integration identifiers.
		 */
		$selected = apply_filters( 'basicrum_automatic_consent_integration', $selected, $detected );

		return in_array( $selected, self::get_supported_integrations(), true ) ? $selected : null;
	}

	/**
	 * Determine whether multiple direct providers require manual selection.
	 *
	 * @return bool Whether the direct-provider result is ambiguous.
	 */
	public static function has_ambiguous_direct_integrations() {
		$detected = self::get_detected_integrations();

		if ( in_array( self::WP_CONSENT_API, $detected, true ) ) {
			return false;
		}

		return 1 < count( array_intersect( $detected, array( self::BORLABS_COOKIE, self::COOKIEYES ) ) );
	}

	/**
	 * Get the packaged JavaScript asset for an integration.
	 *
	 * @param string $integration Integration identifier.
	 * @return string|null Relative asset path, or null when unsupported.
	 */
	public static function get_asset_path( $integration ) {
		$assets = array(
			self::WP_CONSENT_API => 'js/integrations/wp-consent-api.js',
			self::BORLABS_COOKIE => 'js/integrations/borlabs-cookie-v3.js',
			self::COOKIEYES      => 'js/integrations/cookieyes.js',
		);

		return isset( $assets[ $integration ] ) ? $assets[ $integration ] : null;
	}

	/**
	 * Get supported automatic integration identifiers.
	 *
	 * @return array Integration identifiers.
	 */
	private static function get_supported_integrations() {
		return array( self::WP_CONSENT_API, self::BORLABS_COOKIE, self::COOKIEYES );
	}
}
