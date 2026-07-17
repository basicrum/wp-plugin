<?php
/**
 * Base test case for Basicrum plugin tests.
 *
 * @package Basicrum\Tests
 */

namespace Basicrum\WP\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase as BrainMonkeyTestCase;

/**
 * TestCase - base class providing common helper methods for all tests.
 */
class TestCase extends BrainMonkeyTestCase {

	/**
	 * Override settings with test values.
	 *
	 * @param array $overrides Key-value pairs to override.
	 * @return void
	 */
	protected function set_settings( $overrides ) {
		$defaults = \Basicrum\WP\Helpers::get_defaults();
		$settings = array_merge( $defaults, $overrides );

		\Brain\Monkey\Functions\expect( 'get_option' )
			->with( 'basicrum_settings', \Mockery::any() )
			->andReturn( $settings );
	}

	/**
	 * Set the beacon URL for tests.
	 *
	 * @param string $url Beacon URL.
	 * @return void
	 */
	protected function set_beacon_url( $url ) {
		$this->set_settings( array( 'beacon_url' => $url ) );
	}

	/**
	 * Set the Brum Site ID for tests.
	 *
	 * @param string $brum_site_id Brum Site ID.
	 * @return void
	 */
	protected function set_brum_site_id( $brum_site_id ) {
		$this->set_settings( array( 'brum_site_id' => $brum_site_id ) );
	}

	/**
	 * Enable consent-controlled loading.
	 *
	 * @return void
	 */
	protected function enable_consent() {
		$this->set_settings( array( 'consent_enabled' => '1' ) );
	}

	/**
	 * Disable consent-controlled loading.
	 *
	 * @return void
	 */
	protected function disable_consent() {
		$this->set_settings( array( 'consent_enabled' => '0' ) );
	}

	/**
	 * Set delay in milliseconds.
	 *
	 * @param int $ms Milliseconds.
	 * @return void
	 */
	protected function set_delay( $ms ) {
		$this->set_settings( array( 'delay_ms' => $ms ) );
	}
}
