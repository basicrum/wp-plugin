<?php
/**
 * Settings input sanitization and validation.
 *
 * @package Basicrum
 */

namespace Basicrum\WP\Admin\Settings;

use Basicrum\WP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate class - sanitize callback for register_setting().
 */
class Validate {

	/**
	 * Expected Brum Site ID pattern.
	 *
	 * @var string
	 */
	const BRUM_SITE_ID_PATTERN = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

	/**
	 * Allowed script position values.
	 *
	 * @var array
	 */
	const SCRIPT_POSITIONS = array( 'header', 'footer' );

	/**
	 * Sanitize and validate all settings input.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized settings.
	 */
	public function sanitize( $input ) {
		$output   = array();
		$defaults = Helpers::get_defaults();

		// Enabled (checkbox).
		$output['enabled'] = $this->sanitize_checkbox( $input, 'enabled' );

		// Development mode (checkbox).
		$output['development_mode'] = $this->sanitize_checkbox( $input, 'development_mode' );

		// Beacon URL.
		$output['beacon_url'] = $this->sanitize_beacon_url( $input, $defaults, $output['development_mode'] );

		// Brum Site ID.
		$output['brum_site_id'] = $this->sanitize_brum_site_id( $input );

		// Track admins (checkbox).
		$output['track_admins'] = $this->sanitize_checkbox( $input, 'track_admins' );

		// Monitoring start policy.
		$output['consent_enabled'] = $this->sanitize_monitoring_start( $input );

		// Query-string privacy (checkbox).
		$output['strip_query_string'] = $this->sanitize_checkbox( $input, 'strip_query_string' );

		// Wait after onload (checkbox).
		$output['wait_after_onload'] = $this->sanitize_checkbox( $input, 'wait_after_onload' );

		// Delay milliseconds (number).
		$output['delay_ms'] = $this->sanitize_delay( $input, $defaults );

		// Script position (radio).
		$output['script_position'] = $this->sanitize_select(
			$input,
			'script_position',
			self::SCRIPT_POSITIONS,
			$defaults['script_position']
		);

		// Use unminified loaders (checkbox).
		$output['use_unminified_loaders'] = $this->sanitize_checkbox( $input, 'use_unminified_loaders' );

		return $output;
	}

	/**
	 * Sanitize a checkbox value.
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field key.
	 * @return string '1' or '0'.
	 */
	private function sanitize_checkbox( $input, $key ) {
		return ! empty( $input[ $key ] ) ? '1' : '0';
	}

	/**
	 * Sanitize and validate the beacon URL.
	 *
	 * @param array  $input            Raw input.
	 * @param array  $defaults         Default values.
	 * @param string $development_mode Whether development mode is enabled.
	 * @return string Sanitized URL.
	 */
	private function sanitize_beacon_url( $input, $defaults, $development_mode ) {
		if ( empty( $input['beacon_url'] ) ) {
			return $defaults['beacon_url'];
		}

		$url = esc_url_raw( $input['beacon_url'], array( 'https', 'http' ) );

		// Enforce HTTPS unless development mode explicitly allows HTTP.
		if ( 0 === strpos( $url, 'http://' ) && '1' !== $development_mode ) {
			$url = 'https://' . substr( $url, 7 );

			add_settings_error(
				'basicrum_settings',
				'beacon_url_https',
				esc_html__( 'Beacon URL was automatically upgraded to HTTPS.', 'basicrum' ),
				'info'
			);
		}

		if ( empty( $url ) ) {
			add_settings_error(
				'basicrum_settings',
				'beacon_url_invalid',
				esc_html__( 'Invalid Beacon URL. The default URL has been restored.', 'basicrum' ),
				'error'
			);
			return $defaults['beacon_url'];
		}

		return $url;
	}

	/**
	 * Sanitize and validate the Brum Site ID.
	 *
	 * @param array $input Raw input.
	 * @return string Sanitized Brum Site ID or empty string.
	 */
	private function sanitize_brum_site_id( $input ) {
		if ( empty( $input['brum_site_id'] ) ) {
			return '';
		}

		$brum_site_id = sanitize_text_field( $input['brum_site_id'] );

		if ( ! preg_match( self::BRUM_SITE_ID_PATTERN, $brum_site_id ) ) {
			add_settings_error(
				'basicrum_settings',
				'brum_site_id_invalid',
				esc_html__( 'Invalid Brum Site ID. Copy it from the Basicrum backoffice and try again.', 'basicrum' ),
				'error'
			);
			return '';
		}

		return $brum_site_id;
	}

	/**
	 * Sanitize the delay milliseconds value.
	 *
	 * @param array $input    Raw input.
	 * @param array $defaults Default values.
	 * @return int Sanitized delay in milliseconds.
	 */
	private function sanitize_delay( $input, $defaults ) {
		if ( ! isset( $input['delay_ms'] ) ) {
			return (int) $defaults['delay_ms'];
		}

		$delay = absint( $input['delay_ms'] );

		if ( $delay > 30000 ) {
			add_settings_error(
				'basicrum_settings',
				'delay_ms_max',
				esc_html__( 'Delay cannot exceed 30000 milliseconds. It has been capped at 30000.', 'basicrum' ),
				'warning'
			);
			return 30000;
		}

		return $delay;
	}

	/**
	 * Sanitize the monitoring start policy without failing open.
	 *
	 * Invalid or missing values select consent-controlled loading so malformed
	 * input cannot silently start monitoring before a consent signal.
	 *
	 * @param array $input Raw input.
	 * @return string '0' for immediate or '1' for consent-controlled loading.
	 */
	private function sanitize_monitoring_start( $input ) {
		if ( isset( $input['consent_enabled'] ) && in_array( $input['consent_enabled'], array( '0', '1' ), true ) ) {
			return sanitize_text_field( $input['consent_enabled'] );
		}

		add_settings_error(
			'basicrum_settings',
			'consent_enabled_invalid',
			esc_html__( 'Invalid monitoring start value. Basicrum will follow the external consent tool.', 'basicrum' ),
			'warning'
		);

		return '1';
	}

	/**
	 * Sanitize a select/radio value against a whitelist.
	 *
	 * @param array  $input        Raw input.
	 * @param string $key          Field key.
	 * @param array  $allowed      Allowed values.
	 * @param string $fallback_val Fallback value when input is invalid.
	 * @return string Sanitized value.
	 */
	private function sanitize_select( $input, $key, $allowed, $fallback_val ) {
		if ( ! isset( $input[ $key ] ) || ! in_array( $input[ $key ], $allowed, true ) ) {
			return $fallback_val;
		}

		return sanitize_text_field( $input[ $key ] );
	}
}
