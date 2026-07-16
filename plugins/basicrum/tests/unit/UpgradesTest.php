<?php
/**
 * Unit tests for Admin\Upgrades.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Admin\Upgrades;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * UpgradesTest — tests version-based migration logic.
 */
class UpgradesTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function set_up() {
		parent::set_up();

		if ( ! defined( 'BASICRUM_VERSION' ) ) {
			define( 'BASICRUM_VERSION', '1.0.1' );
		}

		// Only stub functions that won't need expectations per-test.
		Functions\when( 'add_action' )->justReturn();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( (int) $val );
		});
	}

	/**
	 * Test that upgrade is skipped when version is current.
	 */
	public function test_skip_upgrade_when_version_current() {
		Functions\expect( 'get_option' )
			->with( 'basicrum_version', '0.0.0' )
			->andReturn( '1.0.1' );

		// update_option should NOT be called.
		Functions\expect( 'update_option' )->never();

		$upgrades = new Upgrades();
		$upgrades->maybe_upgrade();
	}

	/**
	 * Test migration from old PoC options to new format.
	 */
	public function test_migrate_old_options_to_new_format() {
		$old_options = array(
			'url_to_send_data'   => 'https://example.com/beacon',
			'delay_sending_data' => 3000,
			'script_position'    => 'wp_footer',
			'monitoring_type'    => '1.737.60',
		);

		// Route get_option calls based on the key.
		Functions\when( 'get_option' )->alias( function( $key, $default = false ) use ( $old_options ) {
			if ( 'basicrum_version' === $key ) {
				return '1.0.0';
			}
			if ( 'basicrum_options' === $key ) {
				return $old_options;
			}
			return $default;
		});

		// Expect old option to be deleted.
		Functions\expect( 'delete_option' )
			->once()
			->with( 'basicrum_options' );

		// Capture all update_option calls for verification.
		$update_calls = array();
		Functions\when( 'update_option' )->alias( function( $key, $value ) use ( &$update_calls ) {
			$update_calls[ $key ] = $value;
		});

		$upgrades = new Upgrades();
		$upgrades->maybe_upgrade();

		// Verify settings were saved with mapped values.
		$this->assertArrayHasKey( 'basicrum_settings', $update_calls );
		$settings = $update_calls['basicrum_settings'];
		$this->assertSame( 'https://example.com/beacon', $settings['beacon_url'] );
		$this->assertSame( 3000, $settings['delay_ms'] );
		$this->assertSame( 'footer', $settings['script_position'] );
		$this->assertSame( '1', $settings['enabled'] );

		// Verify version was updated.
		$this->assertArrayHasKey( 'basicrum_version', $update_calls );
		$this->assertSame( '1.0.1', $update_calls['basicrum_version'] );
	}
}
