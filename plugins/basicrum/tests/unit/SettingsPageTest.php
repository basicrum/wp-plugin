<?php
/**
 * Unit tests for the admin settings page.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Admin\Settings\Page;
use Basicrum\WP\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * SettingsPageTest - tests settings-page notices.
 */
class SettingsPageTest extends TestCase {

	/**
	 * Test hidden error icons override the default Dashicons display rule.
	 */
	public function test_hidden_error_icon_has_explicit_display_rule() {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/admin/settings.css' );

		$this->assertStringContainsString( '.basicrum-field-error-icon[hidden]', $css );
		$this->assertMatchesRegularExpression( '/\.basicrum-field-error-icon\[hidden\]\s*\{[^}]*display:\s*none;/s', $css );
		$this->assertMatchesRegularExpression( '/\.basicrum-field-error-message\s*\{[^}]*color:\s*#d63638;[^}]*font-weight:\s*600;/s', $css );
	}

	/**
	 * Set up WordPress function stubs.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( 'add_action' )->justReturn();
		Functions\when( 'wp_parse_args' )->alias(
			function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'absint' )->alias(
			function( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'checked' )->alias(
			function( $checked, $current ) {
				return $checked === $current ? 'checked="checked"' : '';
			}
		);
		Functions\when( 'selected' )->alias(
			function( $selected, $current ) {
				return $selected === $current ? 'selected="selected"' : '';
			}
		);
		Functions\when( 'admin_url' )->alias(
			function( $path ) {
				return 'https://example.com/wp-admin/' . $path;
			}
		);
	}

	/**
	 * Test required text fields reflect the enabled and populated state.
	 *
	 * @dataProvider required_field_provider
	 *
	 * @param string $enabled          Enabled setting.
	 * @param string $beacon_url       Beacon URL setting.
	 * @param bool   $expect_required  Whether the HTML required attribute is expected.
	 * @param bool   $expect_invalid   Whether the field should be marked invalid.
	 * @param bool   $expect_error     Whether the inline error should be visible.
	 * @param bool   $expect_disabled  Whether the control should be disabled.
	 */
	public function test_render_text_field_conditional_required_state( $enabled, $beacon_url, $expect_required, $expect_invalid, $expect_error, $expect_disabled ) {
		$this->set_settings(
			array(
				'enabled'    => $enabled,
				'beacon_url' => $beacon_url,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_text_field(
			array(
				'id'                    => 'beacon_url',
				'label'                 => 'Beacon endpoint.',
				'required_when_enabled' => true,
				'required_message'      => 'Beacon URL is required.',
			)
		);
		$html = ob_get_clean();

		$this->assertSame( $expect_required, false !== strpos( $html, 'required="required"' ) );
		$this->assertSame( $expect_required, false !== strpos( $html, 'class="regular-text form-required"' ) );
		$this->assertStringContainsString( 'aria-required="' . ( $expect_required ? 'true' : 'false' ) . '"', $html );
		$this->assertStringContainsString( 'aria-invalid="' . ( $expect_invalid ? 'true' : 'false' ) . '"', $html );
		$this->assertStringContainsString( 'aria-disabled="' . ( $expect_disabled ? 'true' : 'false' ) . '"', $html );
		$this->assertSame( $expect_error, false === strpos( $html, 'basicrum_beacon_url_error" class="description basicrum-field-error-message" hidden' ) );
		$this->assertSame( $expect_error, false === strpos( $html, 'basicrum_beacon_url_error_icon" class="dashicons dashicons-warning basicrum-field-error-icon" aria-hidden="true" hidden' ) );
		$this->assertSame( $expect_disabled, false !== strpos( $html, 'disabled="disabled"' ) );
		$this->assertSame( $expect_disabled, false !== strpos( $html, 'basicrum-disabled-setting-value' ) );
	}

	/**
	 * Provide conditional required-field states.
	 *
	 * @return array[] Test cases.
	 */
	public function required_field_provider() {
		return array(
			'disabled and empty' => array( '0', '', false, false, false, true ),
			'enabled and empty'  => array( '1', '', true, true, true, false ),
			'enabled and set'    => array( '1', 'https://beacon.example.com/catcher', true, false, false, false ),
		);
	}

	/**
	 * Test every operational control type is disabled while monitoring is off.
	 */
	public function test_operational_controls_are_disabled_when_monitoring_is_off() {
		$settings = array_merge(
			\Basicrum\WP\Helpers::get_defaults(),
			array(
				'enabled'         => '0',
				'track_admins'    => '1',
				'delay_ms'       => 500,
				'script_position' => 'footer',
				'consent_mode'    => 'explicit',
			)
		);

		Functions\when( 'get_option' )->justReturn( $settings );

		$page = new Page();

		ob_start();
		$page->render_number_field( array( 'id' => 'delay_ms' ) );
		$page->render_checkbox_field( array( 'id' => 'track_admins' ) );
		$page->render_radio_field(
			array(
				'id'      => 'script_position',
				'options' => array(
					'header' => 'Header',
					'footer' => 'Footer',
				),
			)
		);
		$page->render_select_field(
			array(
				'id'      => 'consent_mode',
				'options' => array( 'explicit' => 'Explicit' ),
			)
		);
		$html = ob_get_clean();

		$this->assertSame( 5, substr_count( $html, 'disabled="disabled"' ) );
		$this->assertSame( 5, substr_count( $html, 'aria-disabled="true"' ) );
		$this->assertSame( 4, substr_count( $html, 'basicrum-disabled-setting-value' ) );
	}

	/**
	 * Test the main monitoring checkbox remains interactive while disabled.
	 */
	public function test_enable_checkbox_remains_interactive_when_monitoring_is_off() {
		$this->set_settings( array( 'enabled' => '0' ) );

		$page = new Page();

		ob_start();
		$page->render_checkbox_field(
			array(
				'id'    => 'enabled',
				'label' => 'Enable monitoring.',
			)
		);
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'disabled="disabled"', $html );
		$this->assertStringContainsString( 'aria-disabled="false"', $html );
		$this->assertStringNotContainsString( 'basicrum-disabled-setting-value', $html );
	}

	/**
	 * Test the conditional settings behavior is loaded on the Basicrum page.
	 */
	public function test_settings_script_is_enqueued_on_basicrum_page() {
		Functions\when( 'plugins_url' )->alias(
			function( $path ) {
				return 'https://example.com/wp-content/plugins/basicrum/' . $path;
			}
		);
		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
				'basicrum-admin-settings',
				'https://example.com/wp-content/plugins/basicrum/assets/js/admin/settings.js',
				array(),
				BASICRUM_VERSION,
				true
			);
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'basicrum-admin-settings-style',
				'https://example.com/wp-content/plugins/basicrum/assets/css/admin/settings.css',
				array(),
				BASICRUM_VERSION
			);

		$page = new Page();
		$page->enqueue_admin_assets( 'toplevel_page_basicrum' );
	}

	/**
	 * Test the settings behavior is not loaded on unrelated admin pages.
	 */
	public function test_settings_script_is_not_enqueued_on_other_pages() {
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();

		$page = new Page();
		$page->enqueue_admin_assets( 'plugins.php' );
	}

	/**
	 * Test enabled monitoring warns when required settings are missing.
	 *
	 * @dataProvider missing_settings_provider
	 *
	 * @param string $beacon_url       Beacon URL setting.
	 * @param string $brum_site_id     Brum Site ID setting.
	 * @param string $expected_message Expected warning fragment.
	 */
	public function test_admin_notice_when_required_settings_are_missing( $beacon_url, $brum_site_id, $expected_message ) {
		$this->set_settings(
			array(
				'enabled'      => '1',
				'beacon_url'   => $beacon_url,
				'brum_site_id' => $brum_site_id,
			)
		);

		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( true );
		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				'basicrum_settings',
				'basicrum_missing_required_settings',
				Mockery::on(
					function( $message ) use ( $expected_message ) {
						return false !== strpos( $message, $expected_message )
							&& false !== strpos( $message, 'admin.php?page=basicrum' );
					}
				),
				'warning'
			);
		Functions\expect( 'settings_errors' )->once();

		$page = new Page();
		$page->admin_notices();
	}

	/**
	 * Provide incomplete required-setting combinations.
	 *
	 * @return array[] Test cases.
	 */
	public function missing_settings_provider() {
		return array(
			'both missing'        => array( '', '', 'Beacon URL and Brum Site ID are missing' ),
			'Beacon URL missing'  => array( '', '550e8400-e29b-41d4-a716-446655440000', 'Beacon URL is missing' ),
			'Brum Site ID missing' => array( 'https://beacon.example.com/catcher', '', 'Brum Site ID is missing' ),
		);
	}

	/**
	 * Test no configuration warning is added when required settings are present.
	 */
	public function test_no_admin_notice_when_required_settings_are_present() {
		$this->set_settings(
			array(
				'enabled'      => '1',
				'beacon_url'   => 'https://beacon.example.com/catcher',
				'brum_site_id' => '550e8400-e29b-41d4-a716-446655440000',
			)
		);

		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( true );
		Functions\expect( 'add_settings_error' )->never();
		Functions\expect( 'settings_errors' )->once();

		$page = new Page();
		$page->admin_notices();
	}

	/**
	 * Test incomplete configuration does not warn while monitoring is disabled.
	 */
	public function test_no_admin_notice_when_monitoring_is_disabled() {
		$this->set_settings(
			array(
				'enabled'      => '0',
				'beacon_url'   => '',
				'brum_site_id' => '',
			)
		);

		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( true );
		Functions\expect( 'add_settings_error' )->never();
		Functions\expect( 'settings_errors' )->once();

		$page = new Page();
		$page->admin_notices();
	}
}
