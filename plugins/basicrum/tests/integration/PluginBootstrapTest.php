<?php
/**
 * Integration smoke tests for plugin bootstrap.
 *
 * @package Basicrum\Tests\Integration
 */

namespace Basicrum\WP\Tests\Integration;

use Basicrum\WP\Helpers;

/**
 * Verifies the plugin loads correctly inside the WordPress test environment.
 */
class PluginBootstrapTest extends \WP_UnitTestCase {

	/**
	 * Ensure core plugin constants and classes are available.
	 *
	 * @return void
	 */
	public function test_plugin_bootstraps_in_wordpress() {
		$this->assertTrue( defined( 'BASICRUM_VERSION' ) );
		$this->assertTrue( defined( 'BASICRUM_PLUGIN_FILE' ) );
		$this->assertTrue( defined( 'BASICRUM_PLUGIN_DIR' ) );
		$this->assertTrue( class_exists( '\\Basicrum\\WP\\Plugin' ) );
	}

	/**
	 * Ensure helper defaults are usable in the live WordPress test environment.
	 *
	 * @return void
	 */
	public function test_helper_defaults_are_available() {
		$defaults = Helpers::get_defaults();

		$this->assertSame( '0', $defaults['enabled'] );
		$this->assertSame( 'footer', $defaults['script_position'] );
	}
}
