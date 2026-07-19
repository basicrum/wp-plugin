<?php
/**
 * Unit tests for the admin settings page.
 *
 * @package Basicrum\Tests\Unit
 */

namespace Basicrum\WP\Tests\Unit;

use Basicrum\WP\Admin\Settings\Page;
use Basicrum\WP\ConsentIntegration;
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
		$this->assertStringContainsString( '.basicrum-settings-header', $css );
		$this->assertStringContainsString( '.basicrum-settings-logo', $css );
		$this->assertStringContainsString( '.basicrum-radio-option', $css );
		$this->assertStringContainsString( '.basicrum-consent-info', $css );
		$this->assertStringContainsString( '.basicrum-consent-connection-hidden', $css );
		$this->assertStringContainsString( '.basicrum-consent-connection-row[hidden]', $css );
		$this->assertStringContainsString( '.basicrum-consent-connection-card', $css );
		$this->assertStringContainsString( '.basicrum-radio-group-legend', $css );
		$this->assertMatchesRegularExpression( '/\.basicrum-consent-connection-card\s*\{[^}]*max-width:\s*none;[^}]*width:\s*100%;/s', $css );
		$this->assertMatchesRegularExpression( '/\.basicrum-consent-connection-card \.basicrum-consent-mode-panel\s*\{[^}]*max-width:\s*none;/s', $css );
		$this->assertStringContainsString( '.basicrum-consent-mode-panel[hidden]', $css );
		$this->assertStringContainsString( '.basicrum-consent-manual', $css );
		$this->assertStringContainsString( '.basicrum-radio-group', $css );
		$this->assertStringContainsString( '.basicrum-consent-status.notice', $css );
		$this->assertStringContainsString( '.basicrum-consent-tier.is-active', $css );
		$this->assertStringContainsString( '.basicrum-consent-tier.is-blocked', $css );
		$this->assertStringContainsString( '.basicrum-consent-detection', $css );
		$this->assertStringContainsString( '.basicrum-consent-diagnostics', $css );
	}

	/**
	 * Test the settings page renders the packaged Basicrum logo.
	 */
	public function test_settings_page_renders_brand_logo() {
		Functions\when( 'plugins_url' )->alias(
			function( $path ) {
				return 'https://example.com/wp-content/plugins/basicrum/' . $path;
			}
		);
		Functions\expect( 'current_user_can' )->once()->with( 'manage_options' )->andReturn( true );
		Functions\when( 'get_admin_page_title' )->justReturn( 'Basicrum Settings' );
		Functions\when( 'settings_fields' )->justReturn();
		Functions\when( 'do_settings_sections' )->justReturn();
		Functions\when( 'submit_button' )->justReturn();

		$page = new Page();

		ob_start();
		$page->render_settings_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'class="basicrum-settings-header"', $html );
		$this->assertStringContainsString( 'class="basicrum-settings-logo"', $html );
		$this->assertStringContainsString( 'src="https://example.com/wp-content/plugins/basicrum/assets/images/basicrum-logo.png"', $html );
		$this->assertStringContainsString( 'alt=""', $html );
		$this->assertStringContainsString( 'width="48"', $html );
		$this->assertStringContainsString( 'height="48"', $html );
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
		Functions\when( 'esc_attr_e' )->alias(
			function( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html_e' )->alias(
			function( $text ) {
				echo $text;
			}
		);
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_textarea' )->returnArg();
		Functions\when( 'sanitize_html_class' )->alias(
			function( $value ) {
				return preg_replace( '/[^A-Za-z0-9_-]/', '', $value );
			}
		);
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
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				return $value;
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
	 * Test the delay field is available only when monitoring and Wait After Onload are active.
	 *
	 * @dataProvider delay_field_availability_provider
	 *
	 * @param string $enabled           Enabled setting value.
	 * @param string $wait_after_onload Wait After Onload setting value.
	 * @param bool   $expect_disabled   Whether the delay field should be disabled.
	 */
	public function test_delay_field_depends_on_wait_after_onload( $enabled, $wait_after_onload, $expect_disabled ) {
		$this->set_settings(
			array(
				'enabled'           => $enabled,
				'wait_after_onload' => $wait_after_onload,
				'delay_ms'          => 500,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_number_field( array( 'id' => 'delay_ms' ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'aria-disabled="' . ( $expect_disabled ? 'true' : 'false' ) . '"', $html );
		$this->assertSame( $expect_disabled, false !== strpos( $html, 'disabled="disabled"' ) );
		$this->assertSame( $expect_disabled, false !== strpos( $html, 'basicrum-disabled-setting-value' ) );
	}

	/**
	 * Provide delay field availability states.
	 *
	 * @return array[] Test cases.
	 */
	public function delay_field_availability_provider() {
		return array(
			'monitoring disabled'       => array( '0', '1', true ),
			'wait after onload disabled' => array( '1', '0', true ),
			'both settings enabled'     => array( '1', '1', false ),
		);
	}

	/**
	 * Test consent tool connection is available only when consent is required.
	 *
	 * @dataProvider consent_connection_availability_provider
	 *
	 * @param string $enabled         Monitoring enabled value.
	 * @param string $consent_enabled Visitor consent requirement value.
	 * @param bool   $expect_disabled Whether the connection radios are disabled.
	 */
	public function test_consent_tool_connection_depends_on_visitor_consent( $enabled, $consent_enabled, $expect_disabled ) {
		$this->set_settings(
			array(
				'enabled'             => $enabled,
				'consent_enabled'     => $consent_enabled,
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_radio_field(
			array(
				'id'      => 'consent_integration',
				'options' => array(
					ConsentIntegration::MODE_AUTOMATIC => 'Automatic',
					ConsentIntegration::MODE_MANUAL    => 'Manual',
				),
			)
		);
		$html = ob_get_clean();

		$this->assertSame( $expect_disabled ? 2 : 0, substr_count( $html, 'disabled="disabled"' ) );
		$this->assertSame( $expect_disabled ? 2 : 0, substr_count( $html, 'aria-disabled="true"' ) );
		$this->assertSame( $expect_disabled, false !== strpos( $html, 'basicrum-disabled-setting-value' ) );
	}

	/**
	 * Provide consent tool connection availability states.
	 *
	 * @return array[] Test cases.
	 */
	public function consent_connection_availability_provider() {
		return array(
			'monitoring disabled' => array( '0', '1', true ),
			'consent not required' => array( '1', '0', true ),
			'consent required'     => array( '1', '1', false ),
		);
	}

	/**
	 * Test the privacy section exposes query protection and real loading behaviors.
	 */
	public function test_privacy_settings_expose_query_protection_and_real_loading_behaviors() {
		$fields = array();

		Functions\when( 'get_option' )->justReturn( \Basicrum\WP\Helpers::get_defaults() );
		Functions\when( 'register_setting' )->justReturn();
		Functions\when( 'add_settings_section' )->justReturn();
		Functions\when( 'add_settings_field' )->alias(
			function() use ( &$fields ) {
				$args               = func_get_args();
				$fields[ $args[0] ] = $args;
			}
		);

		$page = new Page();
		$page->register_settings();

		$this->assertArrayHasKey( 'strip_query_string', $fields );
		$this->assertSame( 'Strip Query Strings', $fields['strip_query_string'][1] );
		$this->assertSame( array( $page, 'render_checkbox_field' ), $fields['strip_query_string'][2] );
		$this->assertSame( 'strip_query_string', $fields['strip_query_string'][5]['id'] );
		$this->assertStringContainsString( 'URL paths are still collected', $fields['strip_query_string'][5]['label'] );
		$this->assertArrayHasKey( 'consent_enabled', $fields );
		$this->assertArrayNotHasKey( 'consent_mode', $fields );
		$this->assertSame( 'Visitor Consent', $fields['consent_enabled'][1] );
		$this->assertSame( array( $page, 'render_radio_field' ), $fields['consent_enabled'][2] );
		$this->assertSame( 'consent_enabled', $fields['consent_enabled'][5]['id'] );
		$this->assertSame( 'Visitor Consent', $fields['consent_enabled'][5]['legend'] );
		$this->assertSame( 'basicrum-consent-requirement-announcement', $fields['consent_enabled'][5]['announcement_class'] );
		$this->assertSame( 'Consent Tool Connection hidden.', $fields['consent_enabled'][5]['announcements']['0'] );
		$this->assertSame( 'Consent Tool Connection shown.', $fields['consent_enabled'][5]['announcements']['1'] );
		$this->assertSame( array( 0, 1 ), array_keys( $fields['consent_enabled'][5]['options'] ) );
		$this->assertSame( 'Monitor without consent', $fields['consent_enabled'][5]['options']['0']['label'] );
		$this->assertSame( 'Require consent before monitoring (recommended)', $fields['consent_enabled'][5]['options']['1']['label'] );
		$this->assertStringContainsString( 'no data is sent until', $fields['consent_enabled'][5]['options']['1']['description'] );
		$this->assertStringContainsString( 'Visitors who decline are not monitored', $fields['consent_enabled'][5]['options']['1']['description'] );
		$this->assertArrayHasKey( 'consent_integration', $fields );
		$this->assertSame( '', $fields['consent_integration'][1] );
		$this->assertSame( array( $page, 'render_consent_connection_field' ), $fields['consent_integration'][2] );
		$this->assertSame( 'consent_integration', $fields['consent_integration'][5]['id'] );
		$this->assertSame( 'basicrum-consent-connection-row', $fields['consent_integration'][5]['class'] );
		$this->assertSame( 'Consent Tool Connection', $fields['consent_integration'][5]['legend'] );
		$this->assertTrue( $fields['consent_integration'][5]['visible_legend'] );
		$this->assertSame( 'Choose how your consent tool\'s decision reaches Basicrum.', $fields['consent_integration'][5]['label'] );
		$this->assertSame(
			array( ConsentIntegration::MODE_AUTOMATIC, ConsentIntegration::MODE_MANUAL ),
			array_keys( $fields['consent_integration'][5]['options'] )
		);
		$this->assertSame( 'Automatic connection (recommended)', $fields['consent_integration'][5]['options'][ ConsentIntegration::MODE_AUTOMATIC ]['label'] );
		$this->assertSame( 'Manual callbacks', $fields['consent_integration'][5]['options'][ ConsentIntegration::MODE_MANUAL ]['label'] );
		$this->assertSame( 'basicrum-consent-automatic-panel', $fields['consent_integration'][5]['options'][ ConsentIntegration::MODE_AUTOMATIC ]['controls'] );
		$this->assertSame( 'basicrum-consent-manual-panel', $fields['consent_integration'][5]['options'][ ConsentIntegration::MODE_MANUAL ]['controls'] );
		$this->assertArrayNotHasKey( 'consent_info', $fields );
	}

	/**
	 * Test connection rows are initially hidden unless visitor consent is required.
	 *
	 * @dataProvider consent_connection_visibility_provider
	 *
	 * @param string $consent_enabled Visitor consent requirement value.
	 * @param string $expected_class  Expected settings row classes.
	 */
	public function test_consent_connection_rows_follow_visitor_consent( $consent_enabled, $expected_class ) {
		$fields = array();

		$this->set_settings( array( 'consent_enabled' => $consent_enabled ) );
		Functions\when( 'register_setting' )->justReturn();
		Functions\when( 'add_settings_section' )->justReturn();
		Functions\when( 'add_settings_field' )->alias(
			function() use ( &$fields ) {
				$args               = func_get_args();
				$fields[ $args[0] ] = $args;
			}
		);

		$page = new Page();
		$page->register_settings();

		$this->assertSame( $expected_class, $fields['consent_integration'][5]['class'] );
	}

	/**
	 * Provide visitor-consent states for connection-row visibility.
	 *
	 * @return array[] Test cases.
	 */
	public function consent_connection_visibility_provider() {
		return array(
			'consent not required' => array( '0', 'basicrum-consent-connection-row basicrum-consent-connection-hidden' ),
			'consent required'     => array( '1', 'basicrum-consent-connection-row' ),
		);
	}

	/**
	 * Test connection radios identify and expose only their selected panel.
	 */
	public function test_integration_radio_controls_are_accessibly_connected() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_radio_field(
			array(
				'id'                 => 'consent_integration',
				'legend'             => 'Consent Tool Connection',
				'label'              => 'Choose one connection mode.',
				'announcement_class' => 'connection-announcement',
				'announcements'      => array(
					'automatic' => 'Automatic shown.',
					'manual'    => 'Manual shown.',
				),
				'options'            => array(
					ConsentIntegration::MODE_AUTOMATIC => array(
						'label'       => 'Automatic',
						'description' => 'Detect a supported provider.',
						'controls'    => 'basicrum-consent-automatic-panel',
					),
					ConsentIntegration::MODE_MANUAL    => array(
						'label'    => 'Manual',
						'controls' => 'basicrum-consent-manual-panel',
					),
				),
			)
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( '<fieldset class="basicrum-radio-group" aria-describedby="basicrum_consent_integration_description">', $html );
		$this->assertStringContainsString( '<legend class="screen-reader-text">Consent Tool Connection</legend>', $html );
		$this->assertMatchesRegularExpression( '/value="automatic"[^>]*aria-controls="basicrum-consent-automatic-panel"/', $html );
		$this->assertMatchesRegularExpression( '/value="automatic"[^>]*aria-describedby="basicrum_consent_integration_automatic_description"/', $html );
		$this->assertMatchesRegularExpression( '/value="manual"[^>]*aria-controls="basicrum-consent-manual-panel"/', $html );
		$this->assertStringContainsString( 'id="basicrum_consent_integration_automatic_description"', $html );
		$this->assertStringContainsString( 'id="basicrum_consent_integration_description"', $html );
		$this->assertTrue( strpos( $html, 'Choose one connection mode.' ) < strpos( $html, 'value="automatic"' ) );
		$this->assertStringContainsString( 'class="screen-reader-text connection-announcement" aria-live="polite" aria-atomic="true"', $html );
		$this->assertStringContainsString( 'data-basicrum-announcement-automatic="Automatic shown."', $html );
		$this->assertStringContainsString( 'data-basicrum-announcement-manual="Manual shown."', $html );
		$this->assertStringContainsString( '</fieldset>', $html );
		$this->assertStringNotContainsString( 'aria-expanded', $html );
	}

	/**
	 * Test the consent connection choice and details share one visual card.
	 */
	public function test_consent_connection_field_wraps_choice_and_details() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_consent_connection_field(
			array(
				'id'             => 'consent_integration',
				'legend'         => 'Consent Tool Connection',
				'visible_legend' => true,
				'label'          => 'Choose one connection mode.',
				'options'        => array(
					ConsentIntegration::MODE_AUTOMATIC => 'Automatic',
					ConsentIntegration::MODE_MANUAL    => 'Manual',
				),
			)
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( '<div class="basicrum-consent-connection-card">', $html );
		$this->assertStringContainsString( '<legend class="basicrum-radio-group-legend">Consent Tool Connection</legend>', $html );
		$this->assertStringContainsString( 'class="basicrum-consent-mode-panels"', $html );
		$this->assertStringContainsString( 'Automatic Connection Status', $html );
	}

	/**
	 * Test consent help accurately describes the integration boundary.
	 */
	public function test_consent_help_describes_external_tool_responsibilities() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_MANUAL,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'does not display a consent popup', $html );
		$this->assertStringContainsString( 'source of truth', $html );
		$this->assertStringContainsString( 'Manual Connection Setup', $html );
		$this->assertStringContainsString( 'Manual callbacks are selected', $html );
		$this->assertStringContainsString( 'same tested adapters included with Basicrum', $html );
		$this->assertStringNotContainsString( 'Consent Tool Integration', $html );
		$this->assertMatchesRegularExpression( '/id="basicrum-consent-automatic-panel"[^>]* hidden/', $html );
		$this->assertDoesNotMatchRegularExpression( '/id="basicrum-consent-manual-panel"[^>]* hidden/', $html );
		$this->assertStringContainsString( 'permission has expired or been withdrawn', $html );
		$this->assertStringContainsString( 'OPT_IN_BASICRUM_LOADER_WRAPPER', $html );
		$this->assertStringContainsString( 'OPT_OUT_BASICRUM_LOADER_WRAPPER', $html );
		$this->assertStringContainsString( 'calls made before registration are not replayed', $html );
		$this->assertStringNotContainsString( 'basicrum:consent-ready', $html );
		$this->assertStringContainsString( 'cannot retract data already sent', $html );
		$this->assertStringContainsString( 'does not persist consent across page loads', $html );
		$this->assertStringContainsString( 'opt-out region', $html );
		$this->assertStringContainsString( 'after Boomerang loading starts', $html );
		$this->assertStringContainsString( 'role="tablist"', $html );
		$this->assertSame( 4, substr_count( $html, 'role="tab"' ) );
		$this->assertSame( 4, substr_count( $html, 'role="tabpanel"' ) );
		$this->assertSame( 6, substr_count( $html, 'class="large-text code"' ) );
		$this->assertSame( 5, substr_count( $html, 'basicrum-copy-consent-snippet' ) );
		$this->assertStringContainsString( 'Borlabs Cookie v3', $html );
		$this->assertStringContainsString( 'WP Consent API', $html );
		$this->assertStringContainsString( 'CookieYes', $html );
		$this->assertStringContainsString( 'Generic', $html );
		$this->assertStringContainsString( 'connected modern CookieYes 3.x installation', $html );
		$this->assertStringContainsString( 'fails closed when the browser API is unavailable', $html );
		$this->assertStringContainsString( 'Automatic detection requires 3.2 or newer', $html );
		$this->assertStringContainsString( 'Complianz does not include WP Consent API by itself', $html );
		$this->assertStringContainsString( 'CookieYes legacy mode uses an incompatible browser API', $html );
		$this->assertStringContainsString( 'Generic Basicrum opt-in callback snippet.', $html );
		$this->assertStringContainsString( 'Generic Basicrum opt-out callback snippet.', $html );
		$this->assertStringNotContainsString( 'integration snippet is unavailable', $html );
	}

	/**
	 * Test automatic handling identifies WP Consent API without exposing manual setup.
	 */
	public function test_consent_help_reports_detected_wp_consent_api() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array( ConsentIntegration::COOKIEYES, ConsentIntegration::WP_CONSENT_API );
				}

				return $value;
			}
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'notice notice-success inline basicrum-consent-status', $html );
		$this->assertStringContainsString( '>Active</span><strong>WP Consent API selected</strong>', $html );
		$this->assertStringContainsString( 'Selected - priority', $html );
		$this->assertStringContainsString( 'Confirm that your consent popup publishes Statistics decisions', $html );
		$this->assertStringContainsString( 'Copy connection status', $html );
		$this->assertStringContainsString( 'Detected providers: WP Consent API, CookieYes', $html );
		$this->assertStringContainsString( 'Selected provider: WP Consent API', $html );
		$this->assertStringContainsString( 'Automatic Connection Status', $html );
		$this->assertStringNotContainsString( 'Consent Tool Integration', $html );
		$this->assertDoesNotMatchRegularExpression( '/id="basicrum-consent-automatic-panel"[^>]* hidden/', $html );
		$this->assertMatchesRegularExpression( '/id="basicrum-consent-manual-panel"[^>]* hidden/', $html );
	}

	/**
	 * Test a consent-less posture makes the connection state explicitly unused.
	 */
	public function test_consent_help_reports_connection_unused_without_consent_requirement() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '0',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( '>Off</span><strong>Consent tool connection is not used</strong>', $html );
		$this->assertStringContainsString( 'Visitor Consent is set to Monitor without consent', $html );
		$this->assertStringContainsString( 'Select Require consent before monitoring', $html );
		$this->assertStringContainsString( 'Connection mode: Automatic', $html );
		$this->assertStringContainsString( 'Visitor consent: Not required', $html );
	}

	/**
	 * Test manual mode prepares an accurate automatic preview before saving.
	 */
	public function test_manual_mode_prepares_detected_automatic_preview() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_MANUAL,
			)
		);
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array( ConsentIntegration::BORLABS_COOKIE );
				}

				return $value;
			}
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/id="basicrum-consent-automatic-panel"[^>]* hidden/', $html );
		$this->assertDoesNotMatchRegularExpression( '/id="basicrum-consent-manual-panel"[^>]* hidden/', $html );
		$this->assertStringContainsString( 'Borlabs Cookie selected', $html );
		$this->assertStringContainsString( 'Selected provider: Borlabs Cookie', $html );
	}

	/**
	 * Test automatic handling clearly pauses for ambiguous direct providers.
	 */
	public function test_consent_help_reports_ambiguous_direct_providers() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array( ConsentIntegration::BORLABS_COOKIE, ConsentIntegration::COOKIEYES );
				}

				return $value;
			}
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'notice notice-error inline basicrum-consent-status', $html );
		$this->assertStringContainsString( '>Blocked</span><strong>Multiple direct providers detected</strong>', $html );
		$this->assertStringContainsString( 'Borlabs Cookie, CookieYes', $html );
		$this->assertStringContainsString( 'It did not guess which provider controls monitoring', $html );
		$this->assertStringContainsString( 'class="button basicrum-open-manual-consent"', $html );
		$this->assertStringContainsString( 'Re-check detection', $html );
	}

	/**
	 * Test Borlabs detection distinguishes selection from configuration work.
	 */
	public function test_consent_help_reports_borlabs_configuration_checklist() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array( ConsentIntegration::BORLABS_COOKIE );
				}

				return $value;
			}
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'notice notice-warning inline basicrum-consent-status', $html );
		$this->assertStringContainsString( '>Action needed</span><strong>Borlabs Cookie selected</strong>', $html );
		$this->assertStringContainsString( 'public Borlabs Cookie 3.2+ PHP API', $html );
		$this->assertStringContainsString( 'Packaged Basicrum adapter selected', $html );
		$this->assertStringContainsString( 'Borlabs service basicrum enabled in the Statistics group', $html );
		$this->assertStringContainsString( 'Re-check detection', $html );
	}

	/**
	 * Test missing detection routes the administrator to the Generic manual tab.
	 */
	public function test_consent_help_routes_missing_provider_to_generic_manual_setup() {
		$this->set_settings(
			array(
				'enabled'             => '1',
				'consent_enabled'     => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
			)
		);
		Functions\when( 'apply_filters' )->alias(
			function( $hook, $value ) {
				if ( 'basicrum_detected_consent_integrations' === $hook ) {
					return array();
				}

				return $value;
			}
		);

		$page = new Page();

		ob_start();
		$page->render_consent_info();
		$html = ob_get_clean();

		$this->assertStringContainsString( '>Blocked</span><strong>No supported provider detected</strong>', $html );
		$this->assertStringContainsString( 'install and activate the standalone WP Consent API plugin', $html );
		$this->assertStringContainsString( 'data-basicrum-manual-tab="generic"', $html );
		$this->assertStringContainsString( 'Re-check detection', $html );
		$this->assertSame( 3, substr_count( $html, '>Not detected</span>' ) );
		$this->assertStringContainsString( 'Detected providers: None', $html );
		$this->assertStringContainsString( 'Selected provider: None', $html );
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
				'consent_enabled' => '1',
				'consent_integration' => ConsentIntegration::MODE_AUTOMATIC,
				'strip_query_string' => '1',
			)
		);

		Functions\when( 'get_option' )->justReturn( $settings );

		$page = new Page();

		ob_start();
		$page->render_number_field( array( 'id' => 'delay_ms' ) );
		$page->render_checkbox_field( array( 'id' => 'track_admins' ) );
		$page->render_checkbox_field( array( 'id' => 'strip_query_string' ) );
		$page->render_radio_field(
			array(
				'id'      => 'script_position',
				'options' => array(
					'header' => 'Header',
					'footer' => 'Footer',
				),
			)
		);
		$page->render_radio_field(
			array(
				'id'      => 'consent_enabled',
				'options' => array(
					'0' => 'Immediate',
					'1' => 'Wait',
				),
			)
		);
		$page->render_radio_field(
			array(
				'id'      => 'consent_integration',
				'options' => array(
					ConsentIntegration::MODE_AUTOMATIC => 'Automatic',
					ConsentIntegration::MODE_MANUAL    => 'Manual',
				),
			)
		);
		$html = ob_get_clean();

		$this->assertSame( 9, substr_count( $html, 'disabled="disabled"' ) );
		$this->assertSame( 9, substr_count( $html, 'aria-disabled="true"' ) );
		$this->assertSame( 6, substr_count( $html, 'basicrum-disabled-setting-value' ) );
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
		$script_version = BASICRUM_VERSION . '.' . filemtime( \Basicrum\WP\Helpers::get_asset_path( 'js/admin/settings.js' ) );
		$style_version  = BASICRUM_VERSION . '.' . filemtime( \Basicrum\WP\Helpers::get_asset_path( 'css/admin/settings.css' ) );

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
				$script_version,
				true
			);
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'basicrum-admin-settings-style',
				'https://example.com/wp-content/plugins/basicrum/assets/css/admin/settings.css',
				array(),
				$style_version
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
