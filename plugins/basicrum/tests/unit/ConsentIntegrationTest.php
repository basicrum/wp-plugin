<?php
/**
 * Unit tests for automatic consent-tool integration selection.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\ConsentIntegration;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test seam for third-party runtime symbols.
 */
class TestableConsentIntegration extends ConsentIntegration {

	/**
	 * Available class names.
	 *
	 * @var array
	 */
	public static $available_classes = array();

	/**
	 * Available function names.
	 *
	 * @var array
	 */
	public static $available_functions = array();

	/**
	 * Check a simulated runtime class.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return bool Whether the class is available.
	 */
	protected static function runtime_class_exists( $class_name ) {
		return in_array( $class_name, self::$available_classes, true );
	}

	/**
	 * Check a simulated runtime function.
	 *
	 * @param string $function_name Function name.
	 * @return bool Whether the function is available.
	 */
	protected static function runtime_function_exists( $function_name ) {
		return in_array( $function_name, self::$available_functions, true );
	}
}

/**
 * ConsentIntegrationTest - tests provider detection and safe selection.
 */
class ConsentIntegrationTest extends TestCase {

	/**
	 * Reset simulated third-party runtime symbols.
	 */
	protected function set_up() {
		parent::set_up();

		TestableConsentIntegration::$available_classes   = array();
		TestableConsentIntegration::$available_functions = array();
	}

	/**
	 * Test Basicrum declares WP Consent API support through the documented filter.
	 */
	public function test_registers_wp_consent_api_support() {
		Functions\when( 'plugin_basename' )->justReturn( 'basicrum/basicrum.php' );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'wp_consent_api_registered_basicrum/basicrum.php', '__return_true' );

		new ConsentIntegration();
	}

	/**
	 * Test the documented WP Consent API runtime markers.
	 *
	 * @dataProvider wp_consent_api_marker_provider
	 *
	 * @param array $classes   Available class names.
	 * @param array $functions Available function names.
	 */
	public function test_detects_wp_consent_api_runtime_markers( $classes, $functions ) {
		TestableConsentIntegration::$available_classes   = $classes;
		TestableConsentIntegration::$available_functions = $functions;

		$this->assertSame( array( ConsentIntegration::WP_CONSENT_API ), TestableConsentIntegration::get_detected_integrations() );
	}

	/**
	 * Provide the two supported WP Consent API markers.
	 *
	 * @return array[] Test cases.
	 */
	public function wp_consent_api_marker_provider() {
		return array(
			'plugin class' => array( array( '\\WP_CONSENT_API' ), array() ),
			'public API'   => array( array(), array( 'wp_has_consent' ) ),
		);
	}

	/**
	 * Test the official Borlabs Cookie 3.2+ PHP API marker.
	 */
	public function test_detects_borlabs_cookie_runtime_marker() {
		TestableConsentIntegration::$available_functions = array( 'borlabsCookieApi' );

		$this->assertSame( array( ConsentIntegration::BORLABS_COOKIE ), TestableConsentIntegration::get_detected_integrations() );
	}

	/**
	 * Test modern CookieYes is detected while its legacy runtime is rejected.
	 */
	public function test_detects_only_modern_cookieyes_runtime() {
		TestableConsentIntegration::$available_classes = array( '\\CookieYes\\Lite\\Includes\\CLI' );

		$this->assertSame( array( ConsentIntegration::COOKIEYES ), TestableConsentIntegration::get_detected_integrations() );

		TestableConsentIntegration::$available_classes = array( '\\Cookie_Law_Info' );

		$this->assertSame( array(), TestableConsentIntegration::get_detected_integrations() );
	}

	/**
	 * Test safe automatic integration selection.
	 *
	 * @dataProvider automatic_selection_provider
	 *
	 * @param array       $detected Detected integration identifiers.
	 * @param string|null $expected Expected selected integration.
	 */
	public function test_selects_one_safe_automatic_integration( $detected, $expected ) {
		$this->stub_detected_integrations( $detected );

		$this->assertSame( $expected, ConsentIntegration::get_automatic_integration() );
	}

	/**
	 * Provide automatic selection cases.
	 *
	 * @return array[] Test cases.
	 */
	public function automatic_selection_provider() {
		return array(
			'none detected'                 => array( array(), null ),
			'WP Consent API only'            => array( array( ConsentIntegration::WP_CONSENT_API ), ConsentIntegration::WP_CONSENT_API ),
			'Borlabs only'                   => array( array( ConsentIntegration::BORLABS_COOKIE ), ConsentIntegration::BORLABS_COOKIE ),
			'CookieYes only'                  => array( array( ConsentIntegration::COOKIEYES ), ConsentIntegration::COOKIEYES ),
			'WP Consent API has priority'     => array(
				array( ConsentIntegration::BORLABS_COOKIE, ConsentIntegration::COOKIEYES, ConsentIntegration::WP_CONSENT_API ),
				ConsentIntegration::WP_CONSENT_API,
			),
			'direct providers are ambiguous' => array(
				array( ConsentIntegration::BORLABS_COOKIE, ConsentIntegration::COOKIEYES ),
				null,
			),
		);
	}

	/**
	 * Test unsupported detected values and duplicates are discarded.
	 */
	public function test_filters_detected_integrations_to_supported_unique_values() {
		$this->stub_detected_integrations(
			array(
				ConsentIntegration::COOKIEYES,
				'unsupported-provider',
				ConsentIntegration::COOKIEYES,
			)
		);

		$this->assertSame( array( ConsentIntegration::COOKIEYES ), ConsentIntegration::get_detected_integrations() );
	}

	/**
	 * Test malformed detection filter output fails closed.
	 */
	public function test_rejects_non_array_detection_filter_output() {
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return ConsentIntegration::COOKIEYES;
				}

				return $value;
			}
		);

		$this->assertSame( array(), ConsentIntegration::get_detected_integrations() );
		$this->assertNull( ConsentIntegration::get_automatic_integration() );
	}

	/**
	 * Test an unsupported selection override cannot become an asset path.
	 */
	public function test_rejects_unsupported_selection_override() {
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array( ConsentIntegration::COOKIEYES );
				}

				if ( 'basicrum_automatic_consent_integration' === $hook ) {
					return 'arbitrary-script';
				}

				return $value;
			}
		);

		$this->assertNull( ConsentIntegration::get_automatic_integration() );
		$this->assertNull( ConsentIntegration::get_asset_path( 'arbitrary-script' ) );
	}

	/**
	 * Test packaged adapter mappings.
	 */
	public function test_maps_supported_integrations_to_packaged_assets() {
		$this->assertSame( 'js/integrations/wp-consent-api.js', ConsentIntegration::get_asset_path( ConsentIntegration::WP_CONSENT_API ) );
		$this->assertSame( 'js/integrations/borlabs-cookie-v3.js', ConsentIntegration::get_asset_path( ConsentIntegration::BORLABS_COOKIE ) );
		$this->assertSame( 'js/integrations/cookieyes.js', ConsentIntegration::get_asset_path( ConsentIntegration::COOKIEYES ) );
	}

	/**
	 * Stub provider detection while preserving the selection filter.
	 *
	 * @param array $detected Detected integration identifiers.
	 * @return void
	 */
	private function stub_detected_integrations( $detected ) {
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) use ( $detected ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return $detected;
				}

				return $value;
			}
		);
	}
}
