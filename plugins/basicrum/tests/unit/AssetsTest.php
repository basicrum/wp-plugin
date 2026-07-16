<?php
/**
 * Unit tests for Assets.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Assets;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * AssetsTest - tests script registration and inline config generation.
 */
class AssetsTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function set_up() {
		parent::set_up();

		if ( ! defined( 'BASICRUM_VERSION' ) ) {
			define( 'BASICRUM_VERSION', '1.0.1' );
		}

		if ( ! defined( 'BASICRUM_PLUGIN_FILE' ) ) {
			define( 'BASICRUM_PLUGIN_FILE', dirname( __DIR__, 2 ) . '/basicrum.php' );
		}

		// Stub WP utility functions.
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'add_filter' )->justReturn();
		Functions\when( 'add_action' )->justReturn();
		Functions\when( 'plugins_url' )->alias( function( $path, $file ) {
			return 'https://example.com/wp-content/plugins/basicrum/' . $path;
		});
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( (int) $val );
		});

		// Stub page type conditionals (default: unknown).
		$conditionals = array(
			'is_front_page', 'is_single', 'is_page', 'is_category',
			'is_tag', 'is_author', 'is_date', 'is_archive', 'is_search', 'is_404',
		);
		foreach ( $conditionals as $func ) {
			Functions\when( $func )->justReturn( false );
		}

		Functions\when( 'class_exists' )->alias( function( $class ) {
			return $class !== 'WooCommerce' && \class_exists( $class );
		});
	}

	/**
	 * Stub apply_filters as a pass-through (returns the second argument).
	 */
	private function stub_apply_filters_passthrough() {
		Functions\when( 'apply_filters' )->alias( function() {
			$args = func_get_args();
			return $args[1];
		});
	}

	/**
	 * Stub wp_parse_args as array_merge.
	 */
	private function stub_wp_parse_args() {
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		});
	}

	/**
	 * Common settings for enabled plugin with defaults.
	 *
	 * @param array $overrides Settings to merge.
	 * @return array
	 */
	private function enabled_settings( $overrides = array() ) {
		return array_merge( array(
			'enabled'                => '1',
			'beacon_url'             => 'https://beacon.example.com/catcher',
			'site_id'                => '',
			'track_admins'           => '0',
			'script_position'        => 'footer',
			'consent_enabled'        => '0',
			'wait_after_onload'      => '0',
			'delay_ms'               => 0,
			'use_unminified_loaders' => '0',
		), $overrides );
	}

	/**
	 * Test that scripts are not enqueued when plugin is disabled.
	 */
	public function test_scripts_not_enqueued_when_disabled() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'enabled' => '0' ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();

		Functions\expect( 'wp_register_script' )->never();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	/**
	 * Test that scripts ARE enqueued when plugin is enabled.
	 */
	public function test_scripts_enqueued_when_enabled() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array(
				'site_id'           => '550e8400-e29b-41d4-a716-446655440000',
				'wait_after_onload' => '1',
				'delay_ms'          => 5000,
			) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		// Expect script registration to happen.
		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->twice(); // config + loader.
		Functions\expect( 'wp_add_inline_script' )->once();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	/**
	 * Test that basicrum_should_track filter can prevent tracking.
	 */
	public function test_should_track_filter_can_prevent_tracking() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'enabled' => '1', 'track_admins' => '0', 'beacon_url' => 'https://beacon.example.com/catcher' ) );

		$this->stub_wp_parse_args();
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		Functions\expect( 'apply_filters' )
			->with( 'basicrum_should_track', true )
			->andReturn( false );

		Functions\expect( 'wp_register_script' )->never();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	/**
	 * Test that scripts are not enqueued when beacon URL is blank.
	 */
	public function test_scripts_not_enqueued_when_beacon_url_is_blank() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( array( 'enabled' => '1', 'track_admins' => '0', 'beacon_url' => '' ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		Functions\expect( 'wp_register_script' )->never();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	// -------------------------------------------------------------------------
	// Inline config content verification tests
	// -------------------------------------------------------------------------

	/**
	 * Test that inline config contains expected p_type, p_gen, brum_site_id,
	 * beacon_url, and basicRumBoomerangConfig values.
	 */
	public function test_inline_config_contains_expected_values() {
		$captured_js = null;

		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array(
				'site_id'           => '550e8400-e29b-41d4-a716-446655440000',
				'wait_after_onload' => '1',
				'delay_ms'          => 5000,
			) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn();
		Functions\when( 'wp_enqueue_script' )->justReturn();

		// Capture the inline script content.
		Functions\expect( 'wp_add_inline_script' )
			->once()
			->andReturnUsing( function( $handle, $js, $position ) use ( &$captured_js ) {
				$captured_js = $js;
			});

		$assets = new Assets();
		$assets->maybe_enqueue();

		$this->assertNotNull( $captured_js, 'Inline script should have been added.' );
		$this->assertStringContainsString( '"p_type"', $captured_js );
		$this->assertStringContainsString( '"p_gen":"wp"', $captured_js );
		$this->assertStringContainsString( '"brum_site_id":"550e8400-e29b-41d4-a716-446655440000"', $captured_js );
		$this->assertStringContainsString( 'beacon.example.com/catcher', $captured_js );
		$this->assertStringContainsString( 'basicRumBoomerangConfig', $captured_js );
		$this->assertStringContainsString( '"instrument_xhr": false', $captured_js );
	}

	/**
	 * Test that beacon URL query parameters are not HTML-encoded in JavaScript.
	 */
	public function test_beacon_url_query_parameters_are_preserved() {
		$captured_js = null;
		$beacon_url  = 'https://beacon.example.com/catcher?site=one&sample=100';

		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array( 'beacon_url' => $beacon_url ) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn();
		Functions\when( 'wp_enqueue_script' )->justReturn();

		Functions\expect( 'wp_add_inline_script' )
			->once()
			->andReturnUsing( function( $handle, $js, $position ) use ( &$captured_js ) {
				$captured_js = $js;
			});

		$assets = new Assets();
		$assets->maybe_enqueue();

		$this->assertNotNull( $captured_js, 'Inline script should have been added.' );
		$this->assertStringContainsString( $beacon_url, $captured_js );
		$this->assertStringNotContainsString( '&#038;', $captured_js );
		$this->assertStringNotContainsString( '&amp;', $captured_js );
	}

	// -------------------------------------------------------------------------
	// Consent mode switching tests
	// -------------------------------------------------------------------------

	/**
	 * Test that standard loader is enqueued when consent is disabled.
	 */
	public function test_standard_loader_when_consent_disabled() {
		$captured_url = null;

		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings() );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn();
		Functions\when( 'wp_add_inline_script' )->justReturn();

		// Capture the loader URL.
		Functions\expect( 'wp_enqueue_script' )
			->twice()
			->andReturnUsing( function( $handle, $src = false ) use ( &$captured_url ) {
				if ( $handle === 'basicrum-loader' ) {
					$captured_url = $src;
				}
			});

		$assets = new Assets();
		$assets->maybe_enqueue();

		$this->assertNotNull( $captured_url, 'Loader script should have been enqueued.' );
		$this->assertStringContainsString( 'boomerang-loader-v15.min.js', $captured_url );
		$this->assertStringNotContainsString( 'consent-', $captured_url );
	}

	/**
	 * Test that consent loader is enqueued when consent is enabled.
	 */
	public function test_consent_loader_when_consent_enabled() {
		$captured_url = null;

		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array(
				'consent_enabled' => '1',
				'consent_mode'    => 'explicit',
			) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn();
		Functions\when( 'wp_add_inline_script' )->justReturn();

		// Capture the loader URL.
		Functions\expect( 'wp_enqueue_script' )
			->twice()
			->andReturnUsing( function( $handle, $src = false ) use ( &$captured_url ) {
				if ( $handle === 'basicrum-loader' ) {
					$captured_url = $src;
				}
			});

		$assets = new Assets();
		$assets->maybe_enqueue();

		$this->assertNotNull( $captured_url, 'Loader script should have been enqueued.' );
		$this->assertStringContainsString( 'consent-boomerang-loader-v1-15.min.js', $captured_url );
	}

	// -------------------------------------------------------------------------
	// Admin user exclusion tests
	// -------------------------------------------------------------------------

	/**
	 * Test that scripts are NOT enqueued for admin users when track_admins is off.
	 */
	public function test_scripts_not_enqueued_for_admin_when_track_admins_off() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array( 'track_admins' => '0' ) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\expect( 'wp_register_script' )->never();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	/**
	 * Test that scripts ARE enqueued for admin users when track_admins is on.
	 */
	public function test_scripts_enqueued_for_admin_when_track_admins_on() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array( 'track_admins' => '1' ) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		// Expect script registration to happen.
		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->twice();
		Functions\expect( 'wp_add_inline_script' )->once();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}

	/**
	 * Test that scripts ARE enqueued for non-admin logged-in users regardless of track_admins.
	 */
	public function test_scripts_enqueued_for_non_admin_logged_in_user() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_settings', array() )
			->andReturn( $this->enabled_settings( array( 'track_admins' => '0' ) ) );

		$this->stub_wp_parse_args();
		$this->stub_apply_filters_passthrough();
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false ); // Not an admin.

		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->twice();
		Functions\expect( 'wp_add_inline_script' )->once();

		$assets = new Assets();
		$assets->maybe_enqueue();
	}
}
