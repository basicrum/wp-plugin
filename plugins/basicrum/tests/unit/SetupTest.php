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
 * SetupTest - tests activation behavior and migration safety.
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
	 * Test a new installation receives defaults and the current version.
	 */
	public function test_new_installation_stores_defaults_and_current_version() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_settings', false )
			->andReturn( false );
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_version', false )
			->andReturn( false );
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_options', false )
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
							&& '0' === $settings['strip_query_string']
							&& ! array_key_exists( 'consent_mode', $settings );
					}
				)
			);
		Functions\expect( 'update_option' )
			->once()
			->with( 'basicrum_version', BASICRUM_VERSION );

		$setup = new Setup();
		$setup->activate();
	}

	/**
	 * Test reactivation preserves an old version so migrations still run.
	 */
	public function test_reactivation_does_not_skip_pending_migrations() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_settings', false )
			->andReturn( array( 'enabled' => '1' ) );
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_version', false )
			->andReturn( '1.0.1' );
		Functions\expect( 'update_option' )->never();

		$setup = new Setup();
		$setup->activate();
	}

	/**
	 * Test a versionless PoC installation is not marked current before migration.
	 */
	public function test_versionless_poc_installation_keeps_migration_pending() {
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_settings', false )
			->andReturn( false );
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_version', false )
			->andReturn( false );
		Functions\expect( 'get_option' )
			->once()
			->with( 'basicrum_options', false )
			->andReturn( array( 'url_to_send_data' => 'https://legacy.example/beacon' ) );

		Functions\expect( 'update_option' )
			->once()
			->with( 'basicrum_settings', Mockery::type( 'array' ) );
		Functions\expect( 'update_option' )
			->with( 'basicrum_version', Mockery::any() )
			->never();

		$setup = new Setup();
		$setup->activate();
	}
}
