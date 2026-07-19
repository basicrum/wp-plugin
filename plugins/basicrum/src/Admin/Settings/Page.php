<?php
/**
 * Admin settings page registration and rendering.
 *
 * @package Basicrum
 */

namespace Basicrum\WP\Admin\Settings;

use Basicrum\WP\ConsentIntegration;
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
			$this->get_admin_asset_version( 'js/admin/settings.js' ),
			true
		);

		wp_enqueue_style(
			self::HANDLE_SETTINGS_STYLE,
			Helpers::get_asset_url( 'css/admin/settings.css' ),
			array(),
			$this->get_admin_asset_version( 'css/admin/settings.css' )
		);
	}

	/**
	 * Get a cache-busting version for an admin asset.
	 *
	 * The plugin version changes for releases. The file modification time also
	 * makes local and development changes take effect without a stale browser
	 * cache continuing to run an earlier settings script.
	 *
	 * @param string $asset Relative asset path.
	 * @return string Asset version.
	 */
	private function get_admin_asset_version( $asset ) {
		$asset_path = Helpers::get_asset_path( $asset );
		$modified   = is_readable( $asset_path ) ? filemtime( $asset_path ) : false;

		if ( false === $modified ) {
			return BASICRUM_VERSION;
		}

		return BASICRUM_VERSION . '.' . $modified;
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
			$this->get_menu_icon(),
			null
		);
	}

	/**
	 * Get the monochrome Basicrum mark for the WordPress admin menu.
	 *
	 * WordPress recolors base64-encoded SVG menu icons to match the active admin
	 * color scheme. Fall back to the analytics Dashicon if the packaged SVG
	 * cannot be read.
	 *
	 * @return string SVG data URI or Dashicon class.
	 */
	private function get_menu_icon() {
		$icon_path = Helpers::get_asset_path( 'images/basicrum-menu-icon.svg' );

		if ( ! is_readable( $icon_path ) ) {
			return 'dashicons-analytics';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a trusted packaged SVG.
		$svg = file_get_contents( $icon_path );

		if ( false === $svg || '' === trim( $svg ) ) {
			return 'dashicons-analytics';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for a WordPress SVG menu icon data URI.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
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
		if ( 'enabled' === $setting_key ) {
			return false;
		}

		if ( '1' !== $settings['enabled'] ) {
			return true;
		}

		if ( 'consent_integration' === $setting_key && '1' !== $settings['consent_enabled'] ) {
			return true;
		}

		return 'delay_ms' === $setting_key && '1' !== $settings['wait_after_onload'];
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
		$settings             = Helpers::get_settings();
		$connection_row_class = 'basicrum-consent-connection-row';

		if ( '1' !== $settings['consent_enabled'] ) {
			$connection_row_class .= ' basicrum-consent-connection-hidden';
		}

		add_settings_section(
			'basicrum_section_privacy',
			esc_html__( 'Visitor Privacy', 'basicrum' ),
			array( $this, 'render_privacy_section_intro' ),
			self::SLUG
		);

		add_settings_field(
			'strip_query_string',
			esc_html__( 'Strip Query Strings', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'    => 'strip_query_string',
				'label' => __( 'When enabled, replace complete query strings in page, navigation, referrer, and resource URLs with ?qs-redacted before sending beacons. URL paths are still collected.', 'basicrum' ),
			)
		);

		add_settings_field(
			'consent_enabled',
			esc_html__( 'Visitor Consent', 'basicrum' ),
			array( $this, 'render_radio_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'                 => 'consent_enabled',
				'legend'             => __( 'Visitor Consent', 'basicrum' ),
				'label'              => __( 'Choose whether Boomerang requires an allow decision before monitoring a visitor.', 'basicrum' ),
				'announcement_class' => 'basicrum-consent-requirement-announcement',
				'announcements'      => array(
					'0' => __( 'Consent Tool Connection hidden.', 'basicrum' ),
					'1' => __( 'Consent Tool Connection shown.', 'basicrum' ),
				),
				'options'            => array(
					'0' => array(
						'label'       => __( 'Monitor without consent', 'basicrum' ),
						'description' => __( 'Boomerang loads for visitors without a consent check. It may set cookies and send performance data. Use this only when your site is permitted to monitor without prior consent.', 'basicrum' ),
					),
					'1' => array(
						'label'       => __( 'Require consent before monitoring (recommended)', 'basicrum' ),
						'description' => __( 'Boomerang stays off and no data is sent until your consent or cookie tool reports an allow decision on each page. Visitors who decline are not monitored. Connect your tool below under Consent Tool Connection.', 'basicrum' ),
					),
				),
			)
		);

		add_settings_field(
			'consent_integration',
			'',
			array( $this, 'render_consent_connection_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'             => 'consent_integration',
				'class'          => $connection_row_class,
				'legend'         => __( 'Consent Tool Connection', 'basicrum' ),
				'visible_legend' => true,
				'label'          => __( 'Choose how your consent tool\'s decision reaches Basicrum.', 'basicrum' ),
				'options'        => array(
					ConsentIntegration::MODE_AUTOMATIC => array(
						'label'       => __( 'Automatic connection (recommended)', 'basicrum' ),
						'description' => __( 'Best for most sites. Detect a supported consent tool and load one matching Basicrum adapter.', 'basicrum' ),
						'controls'    => 'basicrum-consent-automatic-panel',
					),
					ConsentIntegration::MODE_MANUAL    => array(
						'label'       => __( 'Manual callbacks', 'basicrum' ),
						'description' => __( 'For unsupported or custom tools, or when a webmaster already manages the integration snippet.', 'basicrum' ),
						'controls'    => 'basicrum-consent-manual-panel',
					),
				),
			)
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
			esc_html__( 'Control URL query-string handling and choose whether monitoring waits for visitor consent. Basicrum does not display a consent popup, determine your legal basis, or make a site compliant by itself.', 'basicrum' )
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
				'legend'  => __( 'Script Position', 'basicrum' ),
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
			<div class="basicrum-settings-header">
				<img
					class="basicrum-settings-logo"
					src="<?php echo esc_url( Helpers::get_asset_url( 'images/basicrum-logo.png' ) ); ?>"
					alt=""
					width="48"
					height="48"
				/>
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			</div>
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
	 * @param array $args Field arguments: id, legend, visible_legend, label, options,
	 *                    announcement_class, and announcements.
	 * @return void
	 */
	public function render_radio_field( $args ) {
		$settings           = Helpers::get_settings();
		$id                 = isset( $args['id'] ) ? $args['id'] : '';
		$legend             = isset( $args['legend'] ) ? $args['legend'] : '';
		$label              = isset( $args['label'] ) ? $args['label'] : '';
		$options            = isset( $args['options'] ) ? $args['options'] : array();
		$current            = isset( $settings[ $id ] ) ? $settings[ $id ] : '';
		$name               = Helpers::OPTION_KEY . '[' . $id . ']';
		$is_disabled        = $this->is_field_disabled( $id, $settings );
		$legend_class       = ! empty( $args['visible_legend'] ) ? 'basicrum-radio-group-legend' : 'screen-reader-text';
		$announcement_class = isset( $args['announcement_class'] ) ? $args['announcement_class'] : '';
		$announcements      = isset( $args['announcements'] ) ? $args['announcements'] : array();

		$this->render_preserved_disabled_value( $name, $current, $is_disabled );
		$description_id = 'basicrum_' . $id . '_description';

		if ( $legend ) {
			printf(
				'<fieldset class="basicrum-radio-group"%1$s><legend class="%2$s">%3$s</legend>',
				$label ? ' aria-describedby="' . esc_attr( $description_id ) . '"' : '',
				esc_attr( $legend_class ),
				esc_html( $legend )
			);
		}

		if ( $label ) {
			printf( '<p id="%1$s" class="description">%2$s</p>', esc_attr( $description_id ), esc_html( $label ) );
		}

		foreach ( $options as $value => $option ) {
			$option_label          = is_array( $option ) && isset( $option['label'] ) ? $option['label'] : $option;
			$option_description    = is_array( $option ) && isset( $option['description'] ) ? $option['description'] : '';
			$option_controls       = is_array( $option ) && isset( $option['controls'] ) ? $option['controls'] : '';
			$option_id             = 'basicrum_' . $id . '_' . sanitize_html_class( $value );
			$option_description_id = $option_id . '_description';

			printf(
				'<div class="basicrum-radio-option"><label for="%1$s"><input id="%1$s" name="%2$s" type="radio" value="%3$s"',
				esc_attr( $option_id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
			if ( $is_disabled ) {
				echo ' disabled="disabled"';
			}
			if ( $option_controls ) {
				printf(
					' aria-controls="%s"',
					esc_attr( $option_controls )
				);
			}
			if ( $option_description ) {
				printf( ' aria-describedby="%s"', esc_attr( $option_description_id ) );
			}
			printf(
				' aria-disabled="%1$s" %2$s> <strong>%3$s</strong></label>',
				esc_attr( $is_disabled ? 'true' : 'false' ),
				checked( $current, $value, false ),
				esc_html( $option_label )
			);
			if ( $option_description ) {
				printf( '<p id="%1$s" class="description">%2$s</p>', esc_attr( $option_description_id ), esc_html( $option_description ) );
			}
			echo '</div>';
		}
		if ( $announcement_class && $announcements ) {
			printf(
				'<span class="screen-reader-text %1$s" aria-live="polite" aria-atomic="true"',
				esc_attr( $announcement_class )
			);
			foreach ( $announcements as $value => $announcement ) {
				printf(
					' data-basicrum-announcement-%1$s="%2$s"',
					esc_attr( sanitize_html_class( $value ) ),
					esc_attr( $announcement )
				);
			}
			echo '></span>';
		}

		if ( $legend ) {
			echo '</fieldset>';
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
	 * Render the connection choice and its selected setup as one visual group.
	 *
	 * The nested card remains a separate fieldset from Visitor Consent so each
	 * decision has its own accessible legend.
	 *
	 * @param array $args Consent connection radio-field arguments.
	 * @return void
	 */
	public function render_consent_connection_field( $args ) {
		echo '<div class="basicrum-consent-connection-card">';
		$this->render_radio_field( $args );
		$this->render_consent_info();
		echo '</div>';
	}

	/**
	 * Render consent tool connection help.
	 *
	 * @return void
	 */
	public function render_consent_info() {
		$settings              = Helpers::get_settings();
		$detected_integrations = ConsentIntegration::get_detected_integrations();
		$is_automatic          = ConsentIntegration::MODE_AUTOMATIC === $settings['consent_integration'];
		$automatic_integration = ConsentIntegration::get_automatic_integration();
		$automatic_status      = $this->get_automatic_consent_integration_status( $settings, $automatic_integration, $detected_integrations );
		$active_integration    = $automatic_integration;

		if ( null === $active_integration && ! empty( $detected_integrations ) ) {
			$active_integration = $detected_integrations[0];
		}

		if ( null === $active_integration ) {
			$active_integration = ConsentIntegration::BORLABS_COOKIE;
		}

		$integrations = array(
			'borlabs-cookie-v3' => array(
				'label'       => __( 'Borlabs Cookie v3', 'basicrum' ),
				'description' => __( 'The manual adapter supports Borlabs Cookie 3.0.6 or newer. Automatic detection requires 3.2 or newer because it uses the public borlabsCookieApi() marker. Create and enable a Borlabs service with the ID basicrum, normally in the Statistics service group. Add this adapter as unblocked site code that runs on every page.', 'basicrum' ),
				'snippets'    => array(
					array(
						'label' => __( 'Borlabs Cookie adapter', 'basicrum' ),
						'path'  => 'js/integrations/borlabs-cookie-v3.js',
						'rows'  => 18,
					),
				),
			),
			'wp-consent-api'    => array(
				'label'       => __( 'WP Consent API', 'basicrum' ),
				'description' => __( 'Install and activate the standalone WP Consent API plugin, then use this shared adapter with a consent tool such as Complianz or CookieYes that publishes its Statistics decision through the API. Complianz does not include WP Consent API by itself.', 'basicrum' ),
				'snippets'    => array(
					array(
						'label' => __( 'WP Consent API adapter', 'basicrum' ),
						'path'  => 'js/integrations/wp-consent-api.js',
						'rows'  => 18,
					),
				),
			),
			'cookieyes'         => array(
				'label'       => __( 'CookieYes', 'basicrum' ),
				'description' => __( 'Use this direct adapter only for a connected modern CookieYes 3.x installation when WP Consent API is not active. CookieYes legacy mode uses an incompatible browser API and is deliberately not detected. The adapter follows the Analytics category and fails closed when the browser API is unavailable.', 'basicrum' ),
				'snippets'    => array(
					array(
						'label' => __( 'CookieYes adapter', 'basicrum' ),
						'path'  => 'js/integrations/cookieyes.js',
						'rows'  => 18,
					),
				),
			),
			'generic'           => array(
				'label'       => __( 'Generic', 'basicrum' ),
				'description' => __( 'Use these separate snippets when your consent tool provides custom callbacks for an allow decision and a deny, expiry, or withdrawal decision.', 'basicrum' ),
				'snippets'    => array(
					array(
						'label' => __( 'Allow or grant callback', 'basicrum' ),
						'path'  => 'js/integrations/generic-opt-in.js',
						'rows'  => 9,
					),
					array(
						'label' => __( 'Deny, expiry, or withdrawal callback', 'basicrum' ),
						'path'  => 'js/integrations/generic-opt-out.js',
						'rows'  => 9,
					),
				),
			),
		);
		?>
		<div
			class="basicrum-consent-mode-panels"
			data-automatic-announcement="<?php esc_attr_e( 'Automatic connection details shown.', 'basicrum' ); ?>"
			data-manual-announcement="<?php esc_attr_e( 'Manual connection setup shown. Save Changes to apply this mode.', 'basicrum' ); ?>"
		>
			<span class="screen-reader-text basicrum-consent-mode-announcement" aria-live="polite"></span>

			<section
				id="basicrum-consent-automatic-panel"
				class="basicrum-consent-mode-panel basicrum-consent-automatic"
				data-basicrum-consent-integration-panel="<?php echo esc_attr( ConsentIntegration::MODE_AUTOMATIC ); ?>"
				role="region"
				aria-labelledby="basicrum-consent-automatic-heading"
				<?php echo $is_automatic ? '' : ' hidden'; ?>
			>
				<h3 id="basicrum-consent-automatic-heading"><?php esc_html_e( 'Automatic Connection Status', 'basicrum' ); ?></h3>
				<?php $this->render_automatic_consent_integration_status( $automatic_status ); ?>
				<?php $this->render_detected_consent_integrations( $detected_integrations, $automatic_integration ); ?>
				<?php $this->render_consent_integration_diagnostics( $settings, $detected_integrations, $automatic_integration, $automatic_status ); ?>
				<p><?php esc_html_e( 'Your consent tool remains responsible for collecting and reporting the visitor decision. Automatic connection does not configure consent categories or make the site compliant by itself.', 'basicrum' ); ?></p>
			</section>

			<section
				id="basicrum-consent-manual-panel"
				class="basicrum-consent-mode-panel basicrum-consent-manual basicrum-consent-info"
				data-basicrum-consent-integration-panel="<?php echo esc_attr( ConsentIntegration::MODE_MANUAL ); ?>"
				role="region"
				aria-labelledby="basicrum-consent-manual-heading"
				<?php echo $is_automatic ? ' hidden' : ''; ?>
			>
				<h3 id="basicrum-consent-manual-heading"><?php esc_html_e( 'Manual Connection Setup', 'basicrum' ); ?></h3>
				<div class="notice notice-info inline basicrum-consent-status"><p><strong><?php esc_html_e( 'Manual callbacks are selected. Basicrum will not load a provider adapter automatically.', 'basicrum' ); ?></strong></p></div>
				<p><?php esc_html_e( 'Use one matching integration below in your consent or cookie tool. Load it on every page after the Basicrum consent loader.', 'basicrum' ); ?></p>
				<p><code>window.OPT_IN_BASICRUM_LOADER_WRAPPER()</code> - <?php esc_html_e( 'Call when the external tool reports that performance monitoring is allowed. This executes the standard Boomerang loader.', 'basicrum' ); ?></p>
				<p><code>window.OPT_OUT_BASICRUM_LOADER_WRAPPER()</code> - <?php esc_html_e( 'Call when the external tool reports that monitoring is denied or that permission has expired or been withdrawn. Basicrum disables loaded collection and attempts to remove the Boomerang RT and BA cookies, but it cannot retract data already sent.', 'basicrum' ); ?></p>
				<p><?php esc_html_e( 'The callbacks are registered at the configured Script Position. Load the manual integration after that point; calls made before registration are not replayed.', 'basicrum' ); ?></p>
				<p><?php esc_html_e( 'Basicrum does not persist consent across page loads or in its own cookie or server-side record, and it does not display a consent popup. Your consent tool remains the source of truth. A region-aware tool may report allowed before visitor interaction in an opt-out region. If consent is withdrawn after Boomerang loading starts, reload the page before granting it again.', 'basicrum' ); ?></p>
				<p><?php esc_html_e( 'The files below are the same tested adapters included with Basicrum. They do not configure your consent categories or replace legal review.', 'basicrum' ); ?></p>

				<div class="basicrum-consent-tabs">
					<div class="nav-tab-wrapper" role="tablist" aria-label="<?php esc_attr_e( 'Consent tool integrations', 'basicrum' ); ?>">
						<?php foreach ( $integrations as $integration_id => $integration ) : ?>
							<?php $tab_id = 'basicrum-consent-tab-' . $integration_id; ?>
							<?php $is_active_tab = $active_integration === $integration_id; ?>
							<button
								type="button"
								id="<?php echo esc_attr( $tab_id ); ?>"
								class="nav-tab<?php echo $is_active_tab ? ' nav-tab-active' : ''; ?>"
								role="tab"
								aria-selected="<?php echo $is_active_tab ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( 'basicrum-consent-panel-' . $integration_id ); ?>"
								tabindex="<?php echo $is_active_tab ? '0' : '-1'; ?>"
							>
								<?php echo esc_html( $integration['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<?php foreach ( $integrations as $integration_id => $integration ) : ?>
						<?php $is_active_panel = $active_integration === $integration_id; ?>
						<section
							id="<?php echo esc_attr( 'basicrum-consent-panel-' . $integration_id ); ?>"
							class="basicrum-consent-panel<?php echo $is_active_panel ? ' is-active' : ''; ?>"
							role="tabpanel"
							aria-labelledby="<?php echo esc_attr( 'basicrum-consent-tab-' . $integration_id ); ?>"
						>
							<p><?php echo esc_html( $integration['description'] ); ?></p>
							<?php foreach ( $integration['snippets'] as $snippet_index => $snippet ) : ?>
								<?php $this->render_consent_snippet( $integration_id, $snippet_index, $snippet ); ?>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Get the current automatic consent tool connection status.
	 *
	 * @param array       $settings              Plugin settings.
	 * @param string|null $automatic_integration Selected automatic integration.
	 * @param array       $detected_integrations Detected integration identifiers.
	 * @return array Status tier, notice type, verdict, cause, action, and controls.
	 */
	private function get_automatic_consent_integration_status( $settings, $automatic_integration, $detected_integrations ) {
		if ( '1' !== $settings['enabled'] ) {
			return array(
				'tier'        => __( 'Off', 'basicrum' ),
				'tier_class'  => 'is-off',
				'notice_type' => 'info',
				'verdict'     => __( 'Automatic connection is not running', 'basicrum' ),
				'cause'       => __( 'Basicrum monitoring is disabled, so no consent adapter is enqueued.', 'basicrum' ),
				'action'      => __( 'Enable Basicrum monitoring before checking the consent tool connection.', 'basicrum' ),
				'action_type' => '',
				'manual_tab'  => '',
				'checklist'   => array(),
			);
		} elseif ( '1' !== $settings['consent_enabled'] ) {
			return array(
				'tier'        => __( 'Off', 'basicrum' ),
				'tier_class'  => 'is-off',
				'notice_type' => 'info',
				'verdict'     => __( 'Consent tool connection is not used', 'basicrum' ),
				'cause'       => __( 'Visitor Consent is set to Monitor without consent, so Basicrum does not wait for a consent-tool decision.', 'basicrum' ),
				'action'      => __( 'Select Require consent before monitoring if Basicrum should wait for a reported decision.', 'basicrum' ),
				'action_type' => '',
				'manual_tab'  => '',
				'checklist'   => array(),
			);
		} elseif ( ConsentIntegration::WP_CONSENT_API === $automatic_integration ) {
			return array(
				'tier'        => __( 'Active', 'basicrum' ),
				'tier_class'  => 'is-active',
				'notice_type' => 'success',
				'verdict'     => __( 'WP Consent API selected', 'basicrum' ),
				'cause'       => __( 'Basicrum will load its packaged WP Consent API adapter and follow the Statistics decision. This shared API takes priority over direct provider adapters.', 'basicrum' ),
				'action'      => __( 'Confirm that your consent popup publishes Statistics decisions to WP Consent API, then test both allow and deny in a private window.', 'basicrum' ),
				'action_type' => '',
				'manual_tab'  => '',
				'checklist'   => array(),
			);
		} elseif ( ConsentIntegration::BORLABS_COOKIE === $automatic_integration ) {
			return array(
				'tier'        => __( 'Action needed', 'basicrum' ),
				'tier_class'  => 'is-action-needed',
				'notice_type' => 'warning',
				'verdict'     => __( 'Borlabs Cookie selected', 'basicrum' ),
				'cause'       => __( 'Basicrum detected the public Borlabs Cookie 3.2+ PHP API and will load its packaged adapter.', 'basicrum' ),
				'action'      => __( 'Create and enable the Borlabs service with the ID basicrum in the Statistics service group, then re-check and test both decisions in a private window.', 'basicrum' ),
				'action_type' => 'recheck',
				'manual_tab'  => '',
				'checklist'   => array(
					array(
						'state' => 'done',
						'text'  => __( 'Packaged Basicrum adapter selected', 'basicrum' ),
					),
					array(
						'state' => 'verify',
						'text'  => __( 'Borlabs service basicrum enabled in the Statistics group', 'basicrum' ),
					),
				),
			);
		} elseif ( ConsentIntegration::COOKIEYES === $automatic_integration ) {
			return array(
				'tier'        => __( 'Action needed', 'basicrum' ),
				'tier_class'  => 'is-action-needed',
				'notice_type' => 'warning',
				'verdict'     => __( 'CookieYes selected', 'basicrum' ),
				'cause'       => __( 'Basicrum detected the modern CookieYes 3.x runtime and will load its packaged direct adapter. The adapter remains blocked if the connected CookieYes browser API is unavailable.', 'basicrum' ),
				'action'      => __( 'Confirm that CookieYes exposes Analytics consent on the frontend, then re-check and test both decisions in a private window. Prefer WP Consent API when it is available.', 'basicrum' ),
				'action_type' => 'recheck',
				'manual_tab'  => '',
				'checklist'   => array(
					array(
						'state' => 'done',
						'text'  => __( 'Packaged Basicrum adapter selected', 'basicrum' ),
					),
					array(
						'state' => 'verify',
						'text'  => __( 'CookieYes Analytics consent available through the connected browser API', 'basicrum' ),
					),
				),
			);
		} elseif (
			! in_array( ConsentIntegration::WP_CONSENT_API, $detected_integrations, true )
			&& 1 < count( array_intersect( $detected_integrations, array( ConsentIntegration::BORLABS_COOKIE, ConsentIntegration::COOKIEYES ) ) )
		) {
			$provider_labels = $this->get_consent_integration_labels();
			$direct_names    = array();

			foreach ( array( ConsentIntegration::BORLABS_COOKIE, ConsentIntegration::COOKIEYES ) as $integration ) {
				if ( in_array( $integration, $detected_integrations, true ) ) {
					$direct_names[] = $provider_labels[ $integration ];
				}
			}

			/* translators: %s: Comma-separated consent provider names. */
			$cause = sprintf( __( 'Basicrum detected multiple direct providers: %s. It did not guess which provider controls monitoring, so no adapter is loaded.', 'basicrum' ), implode( ', ', $direct_names ) );

			return array(
				'tier'        => __( 'Blocked', 'basicrum' ),
				'tier_class'  => 'is-blocked',
				'notice_type' => 'error',
				'verdict'     => __( 'Multiple direct providers detected', 'basicrum' ),
				'cause'       => $cause,
				'action'      => __( 'Choose Manual callbacks and select the provider that controls Basicrum, or keep only that provider active.', 'basicrum' ),
				'action_type' => 'manual',
				'manual_tab'  => '',
				'checklist'   => array(),
			);
		}

		return array(
			'tier'        => __( 'Blocked', 'basicrum' ),
			'tier_class'  => 'is-blocked',
			'notice_type' => 'error',
			'verdict'     => __( 'No supported provider detected', 'basicrum' ),
			'cause'       => __( 'Basicrum did not find WP Consent API, Borlabs Cookie, or CookieYes. No automatic adapter is loaded and monitoring remains blocked.', 'basicrum' ),
			'action'      => __( 'If you use Complianz, install and activate the standalone WP Consent API plugin. Otherwise use Manual callbacks to connect an unsupported or custom consent tool.', 'basicrum' ),
			'action_type' => 'manual',
			'manual_tab'  => 'generic',
			'checklist'   => array(),
		);
	}

	/**
	 * Render the verdict, cause, and next action for automatic integration.
	 *
	 * @param array $status Automatic connection status.
	 * @return void
	 */
	private function render_automatic_consent_integration_status( $status ) {
		printf(
			'<div class="notice notice-%1$s inline basicrum-consent-status"><p class="basicrum-consent-status-heading"><span class="basicrum-consent-tier %2$s">%3$s</span><strong>%4$s</strong></p><p>%5$s</p>',
			esc_attr( $status['notice_type'] ),
			esc_attr( $status['tier_class'] ),
			esc_html( $status['tier'] ),
			esc_html( $status['verdict'] ),
			esc_html( $status['cause'] )
		);

		if ( ! empty( $status['checklist'] ) ) {
			echo '<ul class="basicrum-consent-checklist">';
			foreach ( $status['checklist'] as $item ) {
				printf(
					'<li class="is-%1$s"><span class="dashicons %2$s" aria-hidden="true"></span>%3$s <strong>%4$s</strong></li>',
					esc_attr( $item['state'] ),
					esc_attr( 'done' === $item['state'] ? 'dashicons-yes-alt' : 'dashicons-search' ),
					esc_html( $item['text'] ),
					esc_html( 'done' === $item['state'] ? __( 'Done', 'basicrum' ) : __( 'Verify', 'basicrum' ) )
				);
			}
			echo '</ul>';
		}

		printf( '<p class="basicrum-consent-next-action"><strong>%1$s</strong> %2$s</p>', esc_html__( 'Next:', 'basicrum' ), esc_html( $status['action'] ) );

		if ( 'manual' === $status['action_type'] ) {
			echo '<p class="basicrum-consent-actions">';
			printf(
				'<button type="button" class="button basicrum-open-manual-consent"%1$s>%2$s</button>',
				$status['manual_tab'] ? ' data-basicrum-manual-tab="' . esc_attr( $status['manual_tab'] ) . '"' : '',
				esc_html__( 'Open manual setup', 'basicrum' )
			);
			printf(
				' <a class="button" href="%1$s">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
				esc_html__( 'Re-check detection', 'basicrum' )
			);
			echo '</p>';
		} elseif ( 'recheck' === $status['action_type'] ) {
			printf(
				'<p><a class="button" href="%1$s">%2$s</a></p>',
				esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
				esc_html__( 'Re-check detection', 'basicrum' )
			);
		}

		echo '</div>';
	}

	/**
	 * Render evidence for each supported consent provider.
	 *
	 * @param array       $detected_integrations Detected integration identifiers.
	 * @param string|null $automatic_integration Selected automatic integration.
	 * @return void
	 */
	private function render_detected_consent_integrations( $detected_integrations, $automatic_integration ) {
		$provider_labels = $this->get_consent_integration_labels();
		?>
		<div class="basicrum-consent-detection">
			<h4><?php esc_html_e( 'Provider detection', 'basicrum' ); ?></h4>
			<ul>
				<?php foreach ( $provider_labels as $integration => $provider_label ) : ?>
					<?php
					$is_detected = in_array( $integration, $detected_integrations, true );
					$is_selected = $automatic_integration === $integration;

					if ( $is_selected && ConsentIntegration::WP_CONSENT_API === $integration ) {
						$state_label = __( 'Selected - priority', 'basicrum' );
						$state_class = 'is-selected';
					} elseif ( $is_selected ) {
						$state_label = __( 'Selected', 'basicrum' );
						$state_class = 'is-selected';
					} elseif ( $is_detected ) {
						$state_label = __( 'Detected', 'basicrum' );
						$state_class = 'is-detected';
					} else {
						$state_label = __( 'Not detected', 'basicrum' );
						$state_class = 'is-not-detected';
					}
					?>
					<li data-basicrum-consent-provider="<?php echo esc_attr( $integration ); ?>">
						<span><?php echo esc_html( $provider_label ); ?></span>
						<span class="basicrum-consent-provider-state <?php echo esc_attr( $state_class ); ?>"><?php echo esc_html( $state_label ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render copyable, non-secret consent tool connection diagnostics.
	 *
	 * @param array       $settings              Plugin settings.
	 * @param array       $detected_integrations Detected integration identifiers.
	 * @param string|null $automatic_integration Selected automatic integration.
	 * @param array       $status                Automatic connection status.
	 * @return void
	 */
	private function render_consent_integration_diagnostics( $settings, $detected_integrations, $automatic_integration, $status ) {
		$provider_labels = $this->get_consent_integration_labels();
		$detected_names  = array();

		foreach ( $provider_labels as $integration => $provider_label ) {
			if ( in_array( $integration, $detected_integrations, true ) ) {
				$detected_names[] = $provider_label;
			}
		}

		$none_label              = __( 'None', 'basicrum' );
		$detected_provider_names = $detected_names ? implode( ', ', $detected_names ) : $none_label;
		$selected_provider_name  = $automatic_integration && isset( $provider_labels[ $automatic_integration ] ) ? $provider_labels[ $automatic_integration ] : $none_label;
		$diagnostics             = array(
			/* translators: %s: Basicrum plugin version. */
			sprintf( __( 'Basicrum version: %s', 'basicrum' ), BASICRUM_VERSION ),
			/* translators: %s: Consent tool connection mode. */
			sprintf( __( 'Connection mode: %s', 'basicrum' ), __( 'Automatic', 'basicrum' ) ),
			/* translators: %s: Enabled or disabled. */
			sprintf( __( 'Monitoring: %s', 'basicrum' ), '1' === $settings['enabled'] ? __( 'Enabled', 'basicrum' ) : __( 'Disabled', 'basicrum' ) ),
			/* translators: %s: Visitor consent requirement. */
			sprintf( __( 'Visitor consent: %s', 'basicrum' ), '1' === $settings['consent_enabled'] ? __( 'Required before monitoring', 'basicrum' ) : __( 'Not required', 'basicrum' ) ),
			/* translators: %s: Comma-separated consent provider names. */
			sprintf( __( 'Detected providers: %s', 'basicrum' ), $detected_provider_names ),
			/* translators: %s: Selected consent provider name. */
			sprintf( __( 'Selected provider: %s', 'basicrum' ), $selected_provider_name ),
			/* translators: 1: Status tier. 2: Status verdict. */
			sprintf( __( 'Result: %1$s - %2$s', 'basicrum' ), $status['tier'], $status['verdict'] ),
		);
		$diagnostic_id           = 'basicrum-consent-integration-diagnostics';
		?>
		<details class="basicrum-consent-diagnostics">
			<summary><?php esc_html_e( 'Connection diagnostics', 'basicrum' ); ?></summary>
			<label class="screen-reader-text" for="<?php echo esc_attr( $diagnostic_id ); ?>"><?php esc_html_e( 'Basicrum consent tool connection diagnostics', 'basicrum' ); ?></label>
			<textarea id="<?php echo esc_attr( $diagnostic_id ); ?>" class="large-text code" rows="7" readonly="readonly" spellcheck="false"><?php echo esc_textarea( implode( "\n", $diagnostics ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'This status contains no Beacon URL or Brum Site ID.', 'basicrum' ); ?></p>
			<p class="basicrum-consent-snippet-actions">
				<button
					type="button"
					class="button basicrum-copy-text"
					data-copy-target="<?php echo esc_attr( $diagnostic_id ); ?>"
					data-copied-label="<?php esc_attr_e( 'Copied', 'basicrum' ); ?>"
					data-copy-fallback-label="<?php esc_attr_e( 'Press Ctrl+C or Command+C to copy.', 'basicrum' ); ?>"
				>
					<?php esc_html_e( 'Copy connection status', 'basicrum' ); ?>
				</button>
				<span class="basicrum-copy-status" aria-live="polite"></span>
			</p>
		</details>
		<?php
	}

	/**
	 * Get administrator-facing consent provider labels.
	 *
	 * @return array Integration identifiers mapped to labels.
	 */
	private function get_consent_integration_labels() {
		return array(
			ConsentIntegration::WP_CONSENT_API => __( 'WP Consent API', 'basicrum' ),
			ConsentIntegration::BORLABS_COOKIE => __( 'Borlabs Cookie', 'basicrum' ),
			ConsentIntegration::COOKIEYES      => __( 'CookieYes', 'basicrum' ),
		);
	}

	/**
	 * Render one copyable consent tool connection snippet.
	 *
	 * @param string $integration_id Integration identifier.
	 * @param int    $snippet_index  Zero-based snippet index.
	 * @param array  $snippet        Snippet label, asset path, and row count.
	 * @return void
	 */
	private function render_consent_snippet( $integration_id, $snippet_index, $snippet ) {
		$snippet_id   = 'basicrum-consent-snippet-' . $integration_id . '-' . $snippet_index;
		$snippet_path = Helpers::get_asset_path( $snippet['path'] );
		$snippet_code = is_readable( $snippet_path ) ? file_get_contents( $snippet_path ) : false;
		?>
		<div class="basicrum-consent-snippet">
			<label for="<?php echo esc_attr( $snippet_id ); ?>"><strong><?php echo esc_html( $snippet['label'] ); ?></strong></label>
			<?php if ( false === $snippet_code ) : ?>
				<p class="notice notice-error inline"><?php esc_html_e( 'This integration snippet is unavailable. Reinstall Basicrum from a complete release package.', 'basicrum' ); ?></p>
			<?php else : ?>
				<textarea
					id="<?php echo esc_attr( $snippet_id ); ?>"
					class="large-text code"
					rows="<?php echo esc_attr( $snippet['rows'] ); ?>"
					readonly="readonly"
					spellcheck="false"
					wrap="off"
				><?php echo esc_textarea( $snippet_code ); ?></textarea>
				<p class="basicrum-consent-snippet-actions">
					<button
						type="button"
						class="button basicrum-copy-text basicrum-copy-consent-snippet"
						data-copy-target="<?php echo esc_attr( $snippet_id ); ?>"
						data-copied-label="<?php esc_attr_e( 'Copied', 'basicrum' ); ?>"
						data-copy-fallback-label="<?php esc_attr_e( 'Press Ctrl+C or Command+C to copy.', 'basicrum' ); ?>"
					>
						<?php esc_html_e( 'Copy snippet', 'basicrum' ); ?>
					</button>
					<span class="basicrum-copy-status" aria-live="polite"></span>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
