<?php
/**
 * Unit tests for Admin\Settings\Validate.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Admin\Settings\Validate;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * ValidateTest - tests input sanitization and validation.
 */
class ValidateTest extends TestCase {

	/**
	 * The validator instance under test.
	 *
	 * @var Validate
	 */
	private $validate;

	/**
	 * Set up test fixtures.
	 */
	protected function set_up() {
		parent::set_up();
		$this->validate = new Validate();

		// Stub WP translation and escaping functions.
		$this->stubTranslationFunctions();
		$this->stubEscapeFunctions();

		// Stub WP functions used in validation.
		Functions\when( 'esc_url_raw' )->alias( function( $url ) {
			return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
		});
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( (int) $val );
		});
		Functions\when( 'add_settings_error' )->justReturn();
	}

	/**
	 * Test a valid Brum Site ID is accepted.
	 */
	public function test_valid_brum_site_id_is_accepted() {
		$input  = array( 'brum_site_id' => '550e8400-e29b-41d4-a716-446655440000' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '550e8400-e29b-41d4-a716-446655440000', $result['brum_site_id'] );
	}

	/**
	 * Test an invalid Brum Site ID is rejected.
	 */
	public function test_invalid_brum_site_id_is_rejected() {
		$input  = array( 'brum_site_id' => 'invalid-site-id' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '', $result['brum_site_id'] );
	}

	/**
	 * Test an empty Brum Site ID is allowed.
	 */
	public function test_empty_brum_site_id_is_allowed() {
		$input  = array( 'brum_site_id' => '' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '', $result['brum_site_id'] );
	}

	/**
	 * Test an unsupported Brum Site ID format is rejected.
	 */
	public function test_unsupported_brum_site_id_format_is_rejected() {
		$input  = array( 'brum_site_id' => '550e8400-e29b-11d4-a716-446655440000' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '', $result['brum_site_id'] );
	}

	/**
	 * Test valid beacon URL is accepted.
	 */
	public function test_valid_beacon_url_is_accepted() {
		$input  = array( 'beacon_url' => 'https://example.com/beacon' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'https://example.com/beacon', $result['beacon_url'] );
	}

	/**
	 * Test HTTP beacon URLs are accepted when development mode is enabled.
	 */
	public function test_http_beacon_url_is_accepted_in_development_mode() {
		$input = array(
			'beacon_url'       => 'http://127.0.0.1:3100/beacon/catcher',
			'development_mode' => '1',
		);
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'http://127.0.0.1:3100/beacon/catcher', $result['beacon_url'] );
		$this->assertSame( '1', $result['development_mode'] );
	}

	/**
	 * Test HTTP beacon URLs are upgraded when development mode is disabled.
	 */
	public function test_http_beacon_url_is_upgraded_without_development_mode() {
		$input  = array( 'beacon_url' => 'http://example.com/beacon' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'https://example.com/beacon', $result['beacon_url'] );
		$this->assertSame( '0', $result['development_mode'] );
	}

	/**
	 * Test delay is capped at 30000ms.
	 */
	public function test_delay_is_capped_at_max() {
		$input  = array( 'delay_ms' => '50000' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 30000, $result['delay_ms'] );
	}

	/**
	 * Test delay accepts valid value.
	 */
	public function test_valid_delay_is_accepted() {
		$input  = array( 'delay_ms' => '3000' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 3000, $result['delay_ms'] );
	}

	/**
	 * Test invalid monitoring start values fail closed.
	 */
	public function test_invalid_monitoring_start_falls_back_to_default() {
		$input  = array( 'consent_enabled' => 'invalid' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '1', $result['consent_enabled'] );
	}

	/**
	 * Test both supported monitoring start values are accepted.
	 *
	 * @dataProvider monitoring_start_provider
	 *
	 * @param string $value Monitoring start value.
	 */
	public function test_valid_monitoring_start_is_accepted( $value ) {
		$input  = array( 'consent_enabled' => $value );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( $value, $result['consent_enabled'] );
	}

	/**
	 * Test query-string stripping can be enabled or disabled.
	 *
	 * @dataProvider query_string_privacy_provider
	 *
	 * @param string $value    Submitted checkbox value.
	 * @param string $expected Sanitized setting value.
	 */
	public function test_query_string_privacy_setting_is_sanitized( $value, $expected ) {
		$input  = array( 'strip_query_string' => $value );
		$result = $this->validate->sanitize( $this->full_input( $input ) );

		$this->assertSame( $expected, $result['strip_query_string'] );
	}

	/**
	 * Provide query-string privacy checkbox states.
	 *
	 * @return array[] Test cases.
	 */
	public function query_string_privacy_provider() {
		return array(
			'enabled'  => array( '1', '1' ),
			'disabled' => array( '0', '0' ),
		);
	}

	/**
	 * Provide supported monitoring start values.
	 *
	 * @return array[] Test cases.
	 */
	public function monitoring_start_provider() {
		return array(
			'immediate loading'          => array( '0' ),
			'consent-controlled loading' => array( '1' ),
		);
	}

	/**
	 * Test the retired consent mode cannot be saved again.
	 */
	public function test_retired_consent_mode_is_not_saved() {
		$input  = array( 'consent_mode' => 'cookie_popup' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );

		$this->assertArrayNotHasKey( 'consent_mode', $result );
	}

	/**
	 * Test script position whitelist.
	 */
	public function test_invalid_script_position_falls_back_to_default() {
		$input  = array( 'script_position' => 'sidebar' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'footer', $result['script_position'] );
	}

	/**
	 * Test checkbox returns '1' when present.
	 */
	public function test_checkbox_enabled() {
		$input  = array( 'enabled' => '1' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '1', $result['enabled'] );
	}

	/**
	 * Test checkbox returns '0' when absent.
	 */
	public function test_checkbox_disabled() {
		$input  = array(); // No 'enabled' key means unchecked.
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( '0', $result['enabled'] );
	}

	/**
	 * Build a full input array with defaults, overridden by given values.
	 *
	 * @param array $overrides Values to override.
	 * @return array
	 */
	private function full_input( $overrides = array() ) {
		$defaults = array(
			'enabled'                => '0',
			'development_mode'       => '0',
			'beacon_url'             => '',
			'brum_site_id'           => '',
			'consent_enabled'        => '0',
			'strip_query_string'     => '0',
			'wait_after_onload'      => '0',
			'delay_ms'               => '0',
			'script_position'        => 'footer',
			'use_unminified_loaders' => '0',
		);

		return array_merge( $defaults, $overrides );
	}
}
