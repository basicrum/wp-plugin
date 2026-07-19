<?php
/**
 * Unit tests for plugin lifecycle setup.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\ConsentIntegration;
use Basicrum\WP\Setup;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * SetupTest - tests activation behavior.
 */
class SetupTest extends TestCase {

	/**
	 * Set up lifecycle hook stubs.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( 'register_activation_hook' )->justReturn();
		Functions\when( 'register_deactivation_hook' )->justReturn();
	}

	/**
	 * Test a new installation receives the complete default settings.
	 */
	public function test_new_installation_stores_defaults() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_settings', false )
			->andReturn( false );
		Functions\expect( 'update_option' )
			->once()
			->with(
				'basicrum_settings',
				Mockery::on(
					function( $settings ) {
						return isset( $settings['consent_enabled'] )
							&& '1' === $settings['consent_enabled']
							&& isset( $settings['consent_integration'] )
							&& ConsentIntegration::MODE_AUTOMATIC === $settings['consent_integration']
							&& isset( $settings['strip_query_string'] )
							&& '0' === $settings['strip_query_string'];
					}
				)
			);

		$setup = new Setup();
		$setup->activate();
	}

	/**
	 * Test reactivation preserves existing settings.
	 */
	public function test_reactivation_preserves_existing_settings() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_settings', false )
			->andReturn( array( 'enabled' => '1' ) );
		Functions\expect( 'update_option' )->never();

		$setup = new Setup();
		$setup->activate();
	}
}
