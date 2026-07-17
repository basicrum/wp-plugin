<?php
/**
 * Admin settings page registration and rendering.
 *
 * @package Basicrum
 */

namespace Basicrum\WP\Admin\Settings;

use Basicrum\WP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page class - registers the admin menu, settings sections, and fields.
 */
class Page {

	/**
	 * Script handle for the settings-page behavior.
	 *
	 * @var string
	 */
	const HANDLE_SETTINGS = 'basicrum-admin-settings';

	/**
	 * Style handle for the settings-page behavior.
	 *
	 * @var string
	 */
	const HANDLE_SETTINGS_STYLE = 'basicrum-admin-settings-style';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	const GROUP = 'basicrum_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const SLUG = 'basicrum';

	/**
	 * Constructor - hook into admin_menu and admin_init.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue behavior used only by the Basicrum settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE_SETTINGS,
			Helpers::get_asset_url( 'js/admin/settings.js' ),
			array(),
			BASICRUM_VERSION,
			true
		);

		wp_enqueue_style(
			self::HANDLE_SETTINGS_STYLE,
			Helpers::get_asset_url( 'css/admin/settings.css' ),
			array(),
			BASICRUM_VERSION
		);
	}

	/**
	 * Add the top-level Basicrum menu page.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_menu_page(
			esc_html__( 'Basicrum Settings', 'basicrum' ),
			esc_html__( 'Basicrum', 'basicrum' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-analytics',
			null
		);
	}

	/**
	 * Register all settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			Helpers::OPTION_KEY,
			array(
				'sanitize_callback' => array( new Validate(), 'sanitize' ),
			)
		);

		$this->add_general_section();
		$this->add_privacy_section();
		$this->add_performance_section();
		$this->add_developer_section();
	}

	/**
	 * Add General Settings section and its fields.
	 *
	 * @return void
	 */
	private function add_general_section() {
		$settings = Helpers::get_settings();

		add_settings_section(
			'basicrum_section_general',
			esc_html__( 'General Settings', 'basicrum' ),
			'__return_empty_string',
			self::SLUG
		);

		add_settings_field(
			'enabled',
			esc_html__( 'Enable Basicrum', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_general',
			array(
				'id'    => 'enabled',
				'label' => __( 'Enable real user monitoring on your site.', 'basicrum' ),
			)
		);

		add_settings_field(
			'beacon_url',
			esc_html__( 'Beacon URL', 'basicrum' ),
			array( $this, 'render_text_field' ),
			self::SLUG,
			'basicrum_section_general',
			array(
				'id'                    => 'beacon_url',
				'label_for'             => 'basicrum_beacon_url',
				'label'                 => __( 'URL where Boomerang beacons are sent. Example: https://www.example.com/beacon/catcher. Required when Basicrum is enabled.', 'basicrum' ),
				'size'                  => 60,
				'class'                 => $this->get_required_field_row_class( $settings, 'beacon_url' ),
				'required_when_enabled' => true,
				'required_message'      => __( 'Beacon URL is required when Basicrum is enabled.', 'basicrum' ),
			)
		);

		add_settings_field(
			'brum_site_id',
			esc_html__( 'Brum Site ID', 'basicrum' ),
			array( $this, 'render_text_field' ),
			self::SLUG,
			'basicrum_section_general',
			array(
				'id'                    => 'brum_site_id',
				'label_for'             => 'basicrum_brum_site_id',
				'label'                 => __( 'Copy the Brum Site ID from the Basicrum backoffice. Required when Basicrum is enabled.', 'basicrum' ),
				'size'                  => 40,
				'class'                 => $this->get_required_field_row_class( $settings, 'brum_site_id' ),
				'required_when_enabled' => true,
				'required_message'      => __( 'Brum Site ID is required when Basicrum is enabled.', 'basicrum' ),
			)
		);

		add_settings_field(
			'track_admins',
			esc_html__( 'Track Admin Users', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_general',
			array(
				'id'    => 'track_admins',
				'label' => __( 'Track logged-in administrators (users with manage_options capability).', 'basicrum' ),
			)
		);

		add_settings_field(
			'boomerang_version',
			esc_html__( 'Boomerang Version', 'basicrum' ),
			array( $this, 'render_boomerang_version' ),
			self::SLUG,
			'basicrum_section_general'
		);
	}

	/**
	 * Get WordPress core classes for an invalid settings row.
	 *
	 * @param array  $settings    Plugin settings.
	 * @param string $setting_key Setting key.
	 * @return string Field row classes.
	 */
	private function get_required_field_row_class( $settings, $setting_key ) {
		if ( '1' !== $settings['enabled'] ) {
			return '';
		}

		if ( ! isset( $settings[ $setting_key ] ) || '' === trim( (string) $settings[ $setting_key ] ) ) {
			return 'form-invalid';
		}

		return '';
	}

	/**
	 * Determine whether a settings control should be disabled.
	 *
	 * @param string $setting_key Setting key.
	 * @param array  $settings    Plugin settings.
	 * @return bool Whether the control should be disabled.
	 */
	private function is_field_disabled( $setting_key, $settings ) {
		return 'enabled' !== $setting_key && '1' !== $settings['enabled'];
	}

	/**
	 * Preserve the value of a disabled control during no-JavaScript submissions.
	 *
	 * Disabled HTML controls are not submitted by browsers.
	 *
	 * @param string $name        Control name.
	 * @param mixed  $value       Stored control value.
	 * @param bool   $is_disabled Whether the visible control is disabled.
	 * @return void
	 */
	private function render_preserved_disabled_value( $name, $value, $is_disabled ) {
		if ( ! $is_disabled ) {
			return;
		}

		printf(
			'<input type="hidden" class="basicrum-disabled-setting-value" name="%1$s" value="%2$s">',
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * Add visitor privacy section and its fields.
	 *
	 * @return void
	 */
	private function add_privacy_section() {
		add_settings_section(
			'basicrum_section_privacy',
			esc_html__( 'Visitor Privacy', 'basicrum' ),
			array( $this, 'render_privacy_section_intro' ),
			self::SLUG
		);

		add_settings_field(
			'consent_enabled',
			esc_html__( 'Monitoring Start', 'basicrum' ),
			array( $this, 'render_radio_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'      => 'consent_enabled',
				'label'   => __( 'Select when Boomerang may load on an eligible page.', 'basicrum' ),
				'options' => array(
					'1' => array(
						'label'       => __( 'Wait for visitor consent', 'basicrum' ),
						'description' => __( 'Boomerang remains blocked until your consent or cookie tool calls the opt-in callback on each page.', 'basicrum' ),
					),
					'0' => array(
						'label'       => __( 'Load immediately', 'basicrum' ),
						'description' => __( 'Boomerang loads on eligible pages without waiting for a consent signal. This may set cookies and send performance data. Use this only when your site is permitted to monitor without prior consent.', 'basicrum' ),
					),
				),
			)
		);

		add_settings_field(
			'consent_info',
			'',
			array( $this, 'render_consent_info' ),
			self::SLUG,
			'basicrum_section_privacy'
		);
	}

	/**
	 * Explain the scope of Basicrum's privacy controls.
	 *
	 * @return void
	 */
	public function render_privacy_section_intro() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Choose when monitoring starts. Basicrum does not display a consent popup, determine your legal basis, or make a site compliant by itself.', 'basicrum' )
		);
	}

	/**
	 * Add Performance section and its fields.
	 *
	 * @return void
	 */
	private function add_performance_section() {
		add_settings_section(
			'basicrum_section_performance',
			esc_html__( 'Performance', 'basicrum' ),
			'__return_empty_string',
			self::SLUG
		);

		add_settings_field(
			'wait_after_onload',
			esc_html__( 'Wait After Onload', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_performance',
			array(
				'id'    => 'wait_after_onload',
				'label' => __( 'Delay beacon sending until after page load completes.', 'basicrum' ),
			)
		);

		add_settings_field(
			'delay_ms',
			esc_html__( 'Delay (milliseconds)', 'basicrum' ),
			array( $this, 'render_number_field' ),
			self::SLUG,
			'basicrum_section_performance',
			array(
				'id'    => 'delay_ms',
				'label' => __( 'Milliseconds to delay the beacon after onload. Leave 0 to disable the delay.', 'basicrum' ),
				'min'   => 0,
				'max'   => 30000,
			)
		);

		add_settings_field(
			'script_position',
			esc_html__( 'Script Position', 'basicrum' ),
			array( $this, 'render_radio_field' ),
			self::SLUG,
			'basicrum_section_performance',
			array(
				'id'      => 'script_position',
				'label'   => __( 'Where to insert the monitoring script.', 'basicrum' ),
				'options' => array(
					'header' => __( 'Header (wp_head)', 'basicrum' ),
					'footer' => __( 'Footer (wp_footer)', 'basicrum' ),
				),
			)
		);
	}

	/**
	 * Add Developer section and its fields.
	 *
	 * @return void
	 */
	private function add_developer_section() {
		add_settings_section(
			'basicrum_section_developer',
			esc_html__( 'Developer Settings', 'basicrum' ),
			'__return_empty_string',
			self::SLUG
		);

		add_settings_field(
			'development_mode',
			esc_html__( 'HTTP Strictness', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_developer',
			array(
				'id'    => 'development_mode',
				'label' => __( 'Allow HTTP beacon URLs for local testing. Do not enable this on production sites.', 'basicrum' ),
			)
		);

		add_settings_field(
			'use_unminified_loaders',
			esc_html__( 'Use Unminified Loaders', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_developer',
			array(
				'id'    => 'use_unminified_loaders',
				'label' => __( 'Load unminified JavaScript loaders for debugging.', 'basicrum' ),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display admin notices for settings errors and incomplete configuration.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->add_required_settings_notice();
		}

		settings_errors();
	}

	/**
	 * Add a warning when monitoring is enabled without its required settings.
	 *
	 * @return void
	 */
	private function add_required_settings_notice() {
		$settings         = Helpers::get_settings();
		$missing_settings = Helpers::get_missing_required_settings( $settings );

		if ( '1' !== $settings['enabled'] || empty( $missing_settings ) ) {
			return;
		}

		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
			esc_html__( 'Basicrum Settings', 'basicrum' )
		);

		if ( in_array( 'beacon_url', $missing_settings, true ) && in_array( 'brum_site_id', $missing_settings, true ) ) {
			/* translators: %s: Link to the Basicrum settings page. */
			$message = sprintf( __( 'Basicrum monitoring is enabled but inactive because the Beacon URL and Brum Site ID are missing. Configure them in %s.', 'basicrum' ), $settings_link );
		} elseif ( in_array( 'beacon_url', $missing_settings, true ) ) {
			/* translators: %s: Link to the Basicrum settings page. */
			$message = sprintf( __( 'Basicrum monitoring is enabled but inactive because the Beacon URL is missing. Configure it in %s.', 'basicrum' ), $settings_link );
		} else {
			/* translators: %s: Link to the Basicrum settings page. */
			$message = sprintf( __( 'Basicrum monitoring is enabled but inactive because the Brum Site ID is missing. Configure it in %s.', 'basicrum' ), $settings_link );
		}

		add_settings_error(
			Helpers::OPTION_KEY,
			'basicrum_missing_required_settings',
			$message,
			'warning'
		);
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field arguments, including conditional required state.
	 * @return void
	 */
	public function render_text_field( $args ) {
		$settings              = Helpers::get_settings();
		$id                    = isset( $args['id'] ) ? $args['id'] : '';
		$label                 = isset( $args['label'] ) ? $args['label'] : '';
		$size                  = isset( $args['size'] ) ? (int) $args['size'] : 40;
		$placeholder           = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$value                 = isset( $settings[ $id ] ) ? $settings[ $id ] : '';
		$name                  = Helpers::OPTION_KEY . '[' . $id . ']';
		$required_when_enabled = ! empty( $args['required_when_enabled'] );
		$required_message      = isset( $args['required_message'] ) ? $args['required_message'] : '';
		$is_required           = $required_when_enabled && '1' === $settings['enabled'];
		$is_invalid            = $is_required && '' === trim( (string) $value );
		$is_disabled           = $this->is_field_disabled( $id, $settings );
		$input_classes         = array( 'regular-text' );
		$description_id        = 'basicrum_' . $id . '_description';
		$error_id              = 'basicrum_' . $id . '_error';
		$describedby           = trim( ( $label ? $description_id : '' ) . ( $required_message ? ' ' . $error_id : '' ) );

		if ( $is_required ) {
			$input_classes[] = 'form-required';
		}

		$this->render_preserved_disabled_value( $name, $value, $is_disabled );

		echo '<span class="basicrum-field-control">';
		printf(
			'<input id="basicrum_%1$s" name="%2$s" type="text" size="%3$d" value="%4$s" placeholder="%5$s" class="%6$s"',
			esc_attr( $id ),
			esc_attr( $name ),
			$size,
			esc_attr( $value ),
			esc_attr( $placeholder ),
			esc_attr( implode( ' ', $input_classes ) )
		);
		if ( $is_required ) {
			echo ' required="required"';
		}
		if ( $is_disabled ) {
			echo ' disabled="disabled"';
		}
		printf(
			' aria-required="%1$s" aria-invalid="%2$s" aria-disabled="%3$s" aria-describedby="%4$s" data-required-message="%5$s">',
			esc_attr( $is_required ? 'true' : 'false' ),
			esc_attr( $is_invalid ? 'true' : 'false' ),
			esc_attr( $is_disabled ? 'true' : 'false' ),
			esc_attr( $describedby ),
			esc_attr( $required_message )
		);
		if ( $is_invalid ) {
			printf( '<span id="%s" class="dashicons dashicons-warning basicrum-field-error-icon" aria-hidden="true"></span>', esc_attr( $error_id . '_icon' ) );
		} else {
			printf( '<span id="%s" class="dashicons dashicons-warning basicrum-field-error-icon" aria-hidden="true" hidden></span>', esc_attr( $error_id . '_icon' ) );
		}
		echo '</span><br>';
		if ( $label ) {
			printf( '<p id="%1$s" class="description">%2$s</p>', esc_attr( $description_id ), esc_html( $label ) );
		}
		if ( $required_message ) {
			if ( $is_invalid ) {
				printf( '<p id="%1$s" class="description basicrum-field-error-message">%2$s</p>', esc_attr( $error_id ), esc_html( $required_message ) );
			} else {
				printf( '<p id="%1$s" class="description basicrum-field-error-message" hidden>%2$s</p>', esc_attr( $error_id ), esc_html( $required_message ) );
			}
		}
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments: id, label, min, max.
	 * @return void
	 */
	public function render_number_field( $args ) {
		$settings    = Helpers::get_settings();
		$id          = isset( $args['id'] ) ? $args['id'] : '';
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$min         = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$max         = isset( $args['max'] ) ? (int) $args['max'] : 30000;
		$value       = isset( $settings[ $id ] ) ? absint( $settings[ $id ] ) : 0;
		$name        = Helpers::OPTION_KEY . '[' . $id . ']';
		$is_disabled = $this->is_field_disabled( $id, $settings );

		$this->render_preserved_disabled_value( $name, $value, $is_disabled );

		printf(
			'<input id="basicrum_%1$s" name="%2$s" type="number" min="%3$d" max="%4$d" value="%5$d"',
			esc_attr( $id ),
			esc_attr( $name ),
			$min,
			$max,
			$value
		);
		if ( $is_disabled ) {
			echo ' disabled="disabled"';
		}
		printf( ' aria-disabled="%s"><br>', esc_attr( $is_disabled ? 'true' : 'false' ) );
		if ( $label ) {
			printf( '<p class="description">%s</p>', esc_html( $label ) );
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments: id, label.
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$settings    = Helpers::get_settings();
		$id          = isset( $args['id'] ) ? $args['id'] : '';
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$value       = isset( $settings[ $id ] ) ? $settings[ $id ] : '0';
		$name        = Helpers::OPTION_KEY . '[' . $id . ']';
		$is_disabled = $this->is_field_disabled( $id, $settings );

		$this->render_preserved_disabled_value( $name, $value, $is_disabled );

		printf(
			'<label><input id="basicrum_%1$s" name="%2$s" type="checkbox" value="1"',
			esc_attr( $id ),
			esc_attr( $name )
		);
		if ( $is_disabled ) {
			echo ' disabled="disabled"';
		}
		printf(
			' aria-disabled="%1$s" %2$s> %3$s</label>',
			esc_attr( $is_disabled ? 'true' : 'false' ),
			checked( '1', $value, false ),
			esc_html( $label )
		);
	}

	/**
	 * Render a radio button field.
	 *
	 * @param array $args Field arguments: id, label, options.
	 * @return void
	 */
	public function render_radio_field( $args ) {
		$settings    = Helpers::get_settings();
		$id          = isset( $args['id'] ) ? $args['id'] : '';
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$options     = isset( $args['options'] ) ? $args['options'] : array();
		$current     = isset( $settings[ $id ] ) ? $settings[ $id ] : '';
		$name        = Helpers::OPTION_KEY . '[' . $id . ']';
		$is_disabled = $this->is_field_disabled( $id, $settings );

		$this->render_preserved_disabled_value( $name, $current, $is_disabled );

		foreach ( $options as $value => $option ) {
			$option_label       = is_array( $option ) && isset( $option['label'] ) ? $option['label'] : $option;
			$option_description = is_array( $option ) && isset( $option['description'] ) ? $option['description'] : '';
			$option_id          = 'basicrum_' . $id . '_' . sanitize_html_class( $value );

			printf(
				'<div class="basicrum-radio-option"><label for="%1$s"><input id="%1$s" name="%2$s" type="radio" value="%3$s"',
				esc_attr( $option_id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
			if ( $is_disabled ) {
				echo ' disabled="disabled"';
			}
			printf(
				' aria-disabled="%1$s" %2$s> <strong>%3$s</strong></label>',
				esc_attr( $is_disabled ? 'true' : 'false' ),
				checked( $current, $value, false ),
				esc_html( $option_label )
			);
			if ( $option_description ) {
				printf( '<p class="description">%s</p>', esc_html( $option_description ) );
			}
			echo '</div>';
		}
		if ( $label ) {
			printf( '<p class="description">%s</p>', esc_html( $label ) );
		}
	}

	/**
	 * Render the Boomerang version (read-only display).
	 *
	 * @return void
	 */
	public function render_boomerang_version() {
		$version = Helpers::get_boomerang_version();
		printf(
			'<p><strong>v%s</strong> <span class="description">(%s)</span></p>',
			esc_html( $version ),
			esc_html__( '~30 KB gzipped', 'basicrum' )
		);
	}

	/**
	 * Render consent integration help.
	 *
	 * @return void
	 */
	public function render_consent_info() {
		$example_code = implode(
			"\n",
			array(
				'function reportBasicrumConsent(allowed) {',
				'  var callbackName = allowed',
				"    ? 'OPT_IN_BASICRUM_LOADER_WRAPPER'",
				"    : 'OPT_OUT_BASICRUM_LOADER_WRAPPER';",
				'',
				"  if (typeof window[callbackName] === 'function') {",
				'    window[callbackName]();',
				'  }',
				'}',
			)
		);
		?>
		<div class="basicrum-consent-info notice notice-info inline">
			<p><strong><?php esc_html_e( 'Consent Tool Integration', 'basicrum' ); ?></strong></p>
			<p><?php esc_html_e( 'When "Wait for visitor consent" is selected, connect the example below to your consent or cookie tool. Call one of the two callbacks on every page after the Basicrum consent loader is available.', 'basicrum' ); ?></p>
			<p><code>window.OPT_IN_BASICRUM_LOADER_WRAPPER()</code> - <?php esc_html_e( 'Call when the visitor allows performance monitoring. This executes the standard Boomerang loader.', 'basicrum' ); ?></p>
			<p><code>window.OPT_OUT_BASICRUM_LOADER_WRAPPER()</code> - <?php esc_html_e( 'Call when consent is rejected, expires, or is withdrawn. Basicrum disables loaded collection and attempts to remove the Boomerang RT and BA cookies, but it cannot retract data already sent.', 'basicrum' ); ?></p>
			<p><?php esc_html_e( 'The callbacks are registered at the configured Script Position. Load the consent integration after that point; calls made before registration are not replayed.', 'basicrum' ); ?></p>
			<p><?php esc_html_e( 'Basicrum does not persist consent across page loads or in its own cookie or server-side record, and it does not display a consent popup. Your consent tool remains the source of truth. If consent is withdrawn after Boomerang loading starts, reload the page before granting it again.', 'basicrum' ); ?></p>
			<p><strong><?php esc_html_e( 'Example:', 'basicrum' ); ?></strong></p>
			<pre><?php echo esc_html( $example_code ); ?></pre>
		</div>
		<?php
	}
}
