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
				'id'    => 'beacon_url',
				'label' => __( 'URL where Boomerang beacons are sent. Example: https://www.example.com/beacon/catcher', 'basicrum' ),
				'size'  => 60,
			)
		);

		add_settings_field(
			'brum_site_id',
			esc_html__( 'Brum Site ID', 'basicrum' ),
			array( $this, 'render_text_field' ),
			self::SLUG,
			'basicrum_section_general',
			array(
				'id'    => 'brum_site_id',
				'label' => __( 'Copy the Brum Site ID from the Basicrum backoffice.', 'basicrum' ),
				'size'  => 40,
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
	 * Add Privacy / GDPR section and its fields.
	 *
	 * @return void
	 */
	private function add_privacy_section() {
		add_settings_section(
			'basicrum_section_privacy',
			esc_html__( 'Privacy / GDPR', 'basicrum' ),
			'__return_empty_string',
			self::SLUG
		);

		add_settings_field(
			'consent_enabled',
			esc_html__( 'Require Consent', 'basicrum' ),
			array( $this, 'render_checkbox_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'    => 'consent_enabled',
				'label' => __( 'When enabled, Boomerang only loads after user gives consent.', 'basicrum' ),
			)
		);

		add_settings_field(
			'consent_mode',
			esc_html__( 'Consent Mode', 'basicrum' ),
			array( $this, 'render_select_field' ),
			self::SLUG,
			'basicrum_section_privacy',
			array(
				'id'      => 'consent_mode',
				'label'   => __( 'How consent is obtained from the user.', 'basicrum' ),
				'options' => array(
					'explicit'      => __( 'Explicit Consent', 'basicrum' ),
					'implicit'      => __( 'Implicit Consent', 'basicrum' ),
					'cookie_banner' => __( 'Cookie Banner', 'basicrum' ),
					'gdpr_banner'   => __( 'GDPR Banner', 'basicrum' ),
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
			'script_position',
			esc_html__( 'Script Position', 'basicrum' ),
			array( $this, 'render_radio_field' ),
			self::SLUG,
			'basicrum_section_developer',
			array(
				'id'      => 'script_position',
				'label'   => __( 'Where to insert the monitoring script.', 'basicrum' ),
				'options' => array(
					'header' => __( 'Header (wp_head)', 'basicrum' ),
					'footer' => __( 'Footer (wp_footer)', 'basicrum' ),
				),
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
	 * Display admin notices for settings errors.
	 *
	 * @return void
	 */
	public function admin_notices() {
		settings_errors();
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field arguments: id, label, size, placeholder.
	 * @return void
	 */
	public function render_text_field( $args ) {
		$settings    = Helpers::get_settings();
		$id          = isset( $args['id'] ) ? $args['id'] : '';
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$size        = isset( $args['size'] ) ? (int) $args['size'] : 40;
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$value       = isset( $settings[ $id ] ) ? esc_attr( $settings[ $id ] ) : '';
		$name        = Helpers::OPTION_KEY . '[' . $id . ']';

		printf(
			'<input id="basicrum_%1$s" name="%2$s" type="text" size="%3$d" value="%4$s" placeholder="%5$s" class="regular-text"><br>',
			esc_attr( $id ),
			esc_attr( $name ),
			$size,
			$value,
			esc_attr( $placeholder )
		);
		if ( $label ) {
			printf( '<p class="description">%s</p>', esc_html( $label ) );
		}
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments: id, label, min, max.
	 * @return void
	 */
	public function render_number_field( $args ) {
		$settings = Helpers::get_settings();
		$id       = isset( $args['id'] ) ? $args['id'] : '';
		$label    = isset( $args['label'] ) ? $args['label'] : '';
		$min      = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$max      = isset( $args['max'] ) ? (int) $args['max'] : 30000;
		$value    = isset( $settings[ $id ] ) ? absint( $settings[ $id ] ) : 0;
		$name     = Helpers::OPTION_KEY . '[' . $id . ']';

		printf(
			'<input id="basicrum_%1$s" name="%2$s" type="number" min="%3$d" max="%4$d" value="%5$d"><br>',
			esc_attr( $id ),
			esc_attr( $name ),
			$min,
			$max,
			$value
		);
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
		$settings = Helpers::get_settings();
		$id       = isset( $args['id'] ) ? $args['id'] : '';
		$label    = isset( $args['label'] ) ? $args['label'] : '';
		$value    = isset( $settings[ $id ] ) ? $settings[ $id ] : '0';
		$name     = Helpers::OPTION_KEY . '[' . $id . ']';

		printf(
			'<label><input id="basicrum_%1$s" name="%2$s" type="checkbox" value="1" %3$s> %4$s</label>',
			esc_attr( $id ),
			esc_attr( $name ),
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
		$settings = Helpers::get_settings();
		$id       = isset( $args['id'] ) ? $args['id'] : '';
		$label    = isset( $args['label'] ) ? $args['label'] : '';
		$options  = isset( $args['options'] ) ? $args['options'] : array();
		$current  = isset( $settings[ $id ] ) ? $settings[ $id ] : '';
		$name     = Helpers::OPTION_KEY . '[' . $id . ']';

		foreach ( $options as $value => $option_label ) {
			printf(
				'<label><input name="%1$s" type="radio" value="%2$s" %3$s> <span>%4$s</span></label><br>',
				esc_attr( $name ),
				esc_attr( $value ),
				checked( $current, $value, false ),
				esc_html( $option_label )
			);
		}
		if ( $label ) {
			printf( '<p class="description">%s</p>', esc_html( $label ) );
		}
	}

	/**
	 * Render a select dropdown field.
	 *
	 * @param array $args Field arguments: id, label, options.
	 * @return void
	 */
	public function render_select_field( $args ) {
		$settings = Helpers::get_settings();
		$id       = isset( $args['id'] ) ? $args['id'] : '';
		$label    = isset( $args['label'] ) ? $args['label'] : '';
		$options  = isset( $args['options'] ) ? $args['options'] : array();
		$current  = isset( $settings[ $id ] ) ? $settings[ $id ] : '';
		$name     = Helpers::OPTION_KEY . '[' . $id . ']';

		printf( '<select id="basicrum_%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
		foreach ( $options as $value => $option_label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $option_label )
			);
		}
		echo '</select>';
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
	 * Render consent mode inline help text with JS API documentation.
	 *
	 * Ported from basicrum-magento-1 ConsentInfo.php.
	 *
	 * @return void
	 */
	public function render_consent_info() {
		?>
		<div class="basicrum-consent-info" style="background: #f0f0f1; padding: 12px 16px; border-left: 4px solid #2271b1; margin: 8px 0;">
			<p><strong><?php esc_html_e( 'Consent JavaScript API', 'basicrum' ); ?></strong></p>
			<p><?php esc_html_e( 'When consent mode is enabled, Boomerang will not load until the user gives consent. Use the following JavaScript API in your consent banner or cookie notice:', 'basicrum' ); ?></p>
			<p><code>window.OPT_IN_BASIC_RUM()</code> - <?php esc_html_e( 'Call this to grant consent and start monitoring. Sets a BRUM_CONSENT cookie (1 year, Strict, Secure).', 'basicrum' ); ?></p>
			<p><code>window.OPT_OUT_BASIC_RUM()</code> - <?php esc_html_e( 'Call this to revoke consent. Disables Boomerang and removes tracking cookies.', 'basicrum' ); ?></p>
			<p><strong><?php esc_html_e( 'Example:', 'basicrum' ); ?></strong></p>
			<pre style="background: #fff; padding: 8px; border: 1px solid #ddd;">&lt;button onclick="OPT_IN_BASIC_RUM()"&gt;Accept&lt;/button&gt;
&lt;button onclick="OPT_OUT_BASIC_RUM()"&gt;Decline&lt;/button&gt;</pre>
		</div>
		<?php
	}
}
