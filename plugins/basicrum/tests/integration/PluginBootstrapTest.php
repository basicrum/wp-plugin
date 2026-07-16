<?php
/**
 * Integration smoke tests for plugin bootstrap.
 *
 * @package Basicrum\Tests\Integration
 */

namespace Basicrum\WP\Tests\Integration;

use Basicrum\WP\Helpers;
use Basicrum\WP\Plugin;

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

	/**
	 * Ensure the bundled translation catalog loads at the normal init priority.
	 *
	 * @return void
	 */
	public function test_translation_loader_uses_default_init_priority() {
		global $wp_filter;

		$callbacks = $wp_filter['init']->callbacks;

		foreach ( $callbacks as $priority => $registered_callbacks ) {
			foreach ( $registered_callbacks as $callback ) {
				$function = $callback['function'];

				if (
					is_array( $function )
					&& $function[0] instanceof Plugin
					&& 'load_textdomain' === $function[1]
				) {
					$this->assertSame( 10, $priority );
					return;
				}
			}
		}

		$this->fail( 'The Basicrum translation loader was not registered.' );
	}
}
