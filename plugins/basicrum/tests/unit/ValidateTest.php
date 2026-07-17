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
	 * Test consent mode whitelist.
	 */
	public function test_invalid_consent_mode_falls_back_to_default() {
		$input  = array( 'consent_mode' => 'invalid_mode' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'explicit', $result['consent_mode'] );
	}

	/**
	 * Test valid consent mode is accepted.
	 */
	public function test_valid_consent_mode_is_accepted() {
		$input  = array( 'consent_mode' => 'cookie_popup' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'cookie_popup', $result['consent_mode'] );
	}

	/**
	 * Test the retired Cookie Banner mode cannot be saved again.
	 */
	public function test_legacy_cookie_banner_mode_falls_back_to_default() {
		$input  = array( 'consent_mode' => 'cookie_banner' );
		$result = $this->validate->sanitize( $this->full_input( $input ) );
		$this->assertSame( 'explicit', $result['consent_mode'] );
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
			'consent_mode'           => 'explicit',
			'wait_after_onload'      => '0',
			'delay_ms'               => '0',
			'script_position'        => 'footer',
			'use_unminified_loaders' => '0',
		);

		return array_merge( $defaults, $overrides );
	}
}
