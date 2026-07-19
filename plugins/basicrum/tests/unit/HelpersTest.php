<?php
/**
 * Unit tests for Helpers.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\ConsentIntegration;
use Basicrum\WP\Helpers;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * HelpersTest - tests settings retrieval and utility methods.
 */
class HelpersTest extends TestCase {

	/**
	 * Test get_defaults returns all expected keys.
	 */
	public function test_get_defaults_has_all_keys() {
		$defaults = Helpers::get_defaults();

		$expected_keys = array(
			'enabled',
			'development_mode',
			'beacon_url',
			'brum_site_id',
			'track_admins',
			'consent_enabled',
			'consent_integration',
			'strip_query_string',
			'wait_after_onload',
			'delay_ms',
			'script_position',
			'use_unminified_loaders',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $defaults, "Missing default key: $key" );
		}

		$this->assertSame( '1', $defaults['consent_enabled'], 'New installations should wait for consent by default.' );
		$this->assertSame( ConsentIntegration::MODE_AUTOMATIC, $defaults['consent_integration'], 'New installations should use automatic consent-tool integration by default.' );
		$this->assertSame( '0', $defaults['strip_query_string'], 'Query strings should be collected by default.' );
	}

	/**
	 * Test default beacon URL is set.
	 */
	public function test_default_beacon_url() {
		$defaults = Helpers::get_defaults();
		$this->assertSame( '', $defaults['beacon_url'] );
	}

	/**
	 * Test get_settings merges stored values with defaults.
	 */
	public function test_get_settings_merges_with_defaults() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'beacon_url' => 'https://custom.example.com/beacon' ) );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$settings = Helpers::get_settings();

		// Custom value preserved.
		$this->assertSame( 'https://custom.example.com/beacon', $settings['beacon_url'] );
		// Defaults filled in.
		$this->assertSame( 0, $settings['delay_ms'] );
		$this->assertSame( ConsentIntegration::MODE_MANUAL, $settings['consent_integration'], 'Existing installations without the setting must remain manual.' );
		$this->assertSame( '0', $settings['strip_query_string'] );
	}

	/**
	 * Test an explicitly saved automatic integration mode is preserved.
	 */
	public function test_get_settings_preserves_explicit_automatic_integration() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'consent_integration' => ConsentIntegration::MODE_AUTOMATIC ) );

		Functions\when( 'wp_parse_args' )->alias(
			function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);

		$settings = Helpers::get_settings();

		$this->assertSame( ConsentIntegration::MODE_AUTOMATIC, $settings['consent_integration'] );
	}

	/**
	 * Test get_settings maps the legacy site ID setting to Brum Site ID.
	 */
	public function test_get_settings_maps_legacy_site_id() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'site_id' => '550e8400-e29b-41d4-a716-446655440000' ) );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$settings = Helpers::get_settings();

		$this->assertSame( '550e8400-e29b-41d4-a716-446655440000', $settings['brum_site_id'] );
		$this->assertArrayNotHasKey( 'site_id', $settings );
	}

	/**
	 * Test get_settings discards the retired consent mode setting.
	 */
	public function test_get_settings_discards_retired_consent_mode() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn(
				array(
					'consent_enabled' => '1',
					'consent_mode'    => 'cookie_popup',
				)
			);

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$settings = Helpers::get_settings();

		$this->assertSame( '1', $settings['consent_enabled'] );
		$this->assertArrayNotHasKey( 'consent_mode', $settings );
	}

	/**
	 * Test programmatic option writes are normalized to the stored schema.
	 */
	public function test_get_settings_normalizes_boolean_like_values() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn(
				array(
					'enabled'                => 1,
					'development_mode'       => true,
					'track_admins'           => 0,
					'consent_enabled'        => 1,
					'strip_query_string'     => false,
					'wait_after_onload'      => '1',
					'use_unminified_loaders' => 'invalid',
				)
			);

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$settings = Helpers::get_settings();

		$this->assertSame( '1', $settings['enabled'] );
		$this->assertSame( '1', $settings['development_mode'] );
		$this->assertSame( '0', $settings['track_admins'] );
		$this->assertSame( '1', $settings['consent_enabled'] );
		$this->assertSame( '0', $settings['strip_query_string'] );
		$this->assertSame( '1', $settings['wait_after_onload'] );
		$this->assertSame( '0', $settings['use_unminified_loaders'] );
	}

	/**
	 * Test malformed option storage falls back without causing a runtime error.
	 */
	public function test_get_settings_handles_non_array_storage() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( 'invalid' );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$settings = Helpers::get_settings();

		$this->assertSame( '0', $settings['enabled'] );
		$this->assertSame( '1', $settings['consent_enabled'] );
		$this->assertSame( ConsentIntegration::MODE_MANUAL, $settings['consent_integration'] );
	}

	/**
	 * Test consent-controlled loading status.
	 *
	 * @dataProvider consent_enabled_provider
	 *
	 * @param mixed  $stored_value Stored consent gate value.
	 * @param bool   $expected     Expected enabled state.
	 */
	public function test_is_consent_enabled( $stored_value, $expected ) {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'consent_enabled' => $stored_value ) );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$this->assertSame( $expected, Helpers::is_consent_enabled() );
	}

	/**
	 * Provide consent-controlled loading states.
	 *
	 * @return array[] Test cases.
	 */
	public function consent_enabled_provider() {
		return array(
			'immediate loading'          => array( '0', false ),
			'consent-controlled loading' => array( '1', true ),
			'integer immediate loading'  => array( 0, false ),
			'integer consent loading'    => array( 1, true ),
			'boolean immediate loading'  => array( false, false ),
			'boolean consent loading'    => array( true, true ),
		);
	}

	/**
	 * Test get_boomerang_version returns expected version.
	 */
	public function test_boomerang_version() {
		$this->assertSame( '1.815.60', Helpers::get_boomerang_version() );
	}

	/**
	 * Test is_enabled returns false when disabled.
	 */
	public function test_is_enabled_returns_false_when_disabled() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'enabled' => '0' ) );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$this->assertFalse( Helpers::is_enabled() );
	}

	/**
	 * Test is_enabled returns true when enabled.
	 */
	public function test_is_enabled_returns_true_when_enabled() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'enabled' => '1' ) );

		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});

		$this->assertTrue( Helpers::is_enabled() );
	}

	/**
	 * Test required settings are reported when they are blank.
	 */
	public function test_get_missing_required_settings_reports_blank_values() {
		$missing_settings = Helpers::get_missing_required_settings(
			array(
				'beacon_url'   => '',
				'brum_site_id' => '   ',
			)
		);

		$this->assertSame( array( 'beacon_url', 'brum_site_id' ), $missing_settings );
	}

	/**
	 * Test a complete monitoring configuration has no missing settings.
	 */
	public function test_get_missing_required_settings_accepts_populated_values() {
		$missing_settings = Helpers::get_missing_required_settings(
			array(
				'beacon_url'   => 'https://beacon.example.com/catcher',
				'brum_site_id' => '550e8400-e29b-41d4-a716-446655440000',
			)
		);

		$this->assertSame( array(), $missing_settings );
	}
}
