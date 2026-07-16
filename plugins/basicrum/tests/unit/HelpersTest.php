<?php
/**
 * Unit tests for Helpers.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

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
			'beacon_url',
			'brum_site_id',
			'track_admins',
			'consent_enabled',
			'consent_mode',
			'wait_after_onload',
			'delay_ms',
			'script_position',
			'use_unminified_loaders',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $defaults, "Missing default key: $key" );
		}
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
}
