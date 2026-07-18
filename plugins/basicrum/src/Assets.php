<?php
/**
 * Frontend script injection for Boomerang RUM.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets class - enqueues the Boomerang library and inline configuration
 * on the frontend using proper WordPress script APIs.
 *
 * Replaces the raw heredoc injection from the PoC's core-functions.php.
 */
class Assets {

	/**
	 * Script handle for the Boomerang loader.
	 *
	 * @var string
	 */
	const HANDLE_LOADER = 'basicrum-loader';

	/**
	 * Script handle for the inline config.
	 *
	 * @var string
	 */
	const HANDLE_CONFIG = 'basicrum-config';

	/**
	 * Constructor - register the enqueue hook.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 1 );

		// Add data-cfasync="false" to bypass Cloudflare Rocket Loader.
		add_filter( 'script_loader_tag', array( $this, 'add_cfasync_attribute' ), 10, 2 );
	}

	/**
	 * Conditionally enqueue Basicrum scripts.
	 *
	 * @return void
	 */
	public function maybe_enqueue() {
		$settings = Helpers::get_settings();

		if ( ! $this->should_track( $settings ) ) {
			return;
		}

		$in_footer = ( 'footer' === $settings['script_position'] );

		$this->enqueue_config_script( $settings, $in_footer );
		$this->enqueue_loader_script( $settings, $in_footer );
	}

	/**
	 * Determine whether we should inject tracking scripts.
	 *
	 * @param array $settings Plugin settings.
	 * @return bool
	 */
	private function should_track( $settings ) {
		if ( empty( $settings['enabled'] ) || '1' !== $settings['enabled'] ) {
			return false;
		}

		// Both identifiers are required before any monitoring scripts are injected.
		if ( ! empty( Helpers::get_missing_required_settings( $settings ) ) ) {
			return false;
		}

		// Skip tracking for admin users when track_admins is disabled.
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) && '1' !== $settings['track_admins'] ) {
			return false;
		}

		/**
		 * Filter whether Basicrum should track the current page.
		 *
		 * Returning false will prevent all script injection.
		 *
		 * @param bool $should_track Whether to track.
		 */
		return (bool) apply_filters( 'basicrum_should_track', true );
	}

	/**
	 * Enqueue the inline Boomerang configuration script.
	 *
	 * @param array $settings Plugin settings.
	 * @param bool  $in_footer Whether to load in footer.
	 * @return void
	 */
	private function enqueue_config_script( $settings, $in_footer ) {
		// Register a dummy handle for attaching inline scripts.
		wp_register_script( self::HANDLE_CONFIG, false, array(), BASICRUM_VERSION, $in_footer );
		wp_enqueue_script( self::HANDLE_CONFIG );

		$config_js = $this->build_config_js( $settings );
		wp_add_inline_script( self::HANDLE_CONFIG, $config_js, 'after' );
	}

	/**
	 * Enqueue the Boomerang loader script.
	 *
	 * Selects between standard and consent loader based on settings.
	 *
	 * @param array $settings Plugin settings.
	 * @param bool  $in_footer Whether to load in footer.
	 * @return void
	 */
	private function enqueue_loader_script( $settings, $in_footer ) {
		$use_unminified  = ! empty( $settings['use_unminified_loaders'] ) && '1' === $settings['use_unminified_loaders'];
		$consent_enabled = ! empty( $settings['consent_enabled'] ) && '1' === $settings['consent_enabled'];

		if ( $consent_enabled ) {
			$loader_file = $use_unminified
				? 'js/loaders/consent-boomerang-loader-v1-15.js'
				: 'js/loaders/consent-boomerang-loader-v1-15.min.js';
		} else {
			$loader_file = $use_unminified
				? 'js/loaders/boomerang-loader-v15.js'
				: 'js/loaders/boomerang-loader-v15.min.js';
		}

		$loader_url = Helpers::get_asset_url( $loader_file );

		wp_enqueue_script(
			self::HANDLE_LOADER,
			$loader_url,
			array( self::HANDLE_CONFIG ),
			BASICRUM_VERSION,
			$in_footer
		);
	}

	/**
	 * Build the inline JavaScript configuration for Boomerang.
	 *
	 * @param array $settings Plugin settings.
	 * @return string JavaScript code.
	 */
	private function build_config_js( $settings ) {
		$page_type_detector = new PageTypeDetector();
		$page_type          = $page_type_detector->detect();
		$beacon_url         = esc_url_raw( $settings['beacon_url'] );
		$brum_site_id       = sanitize_text_field( $settings['brum_site_id'] );
		$boomerang_version  = Helpers::get_boomerang_version();
		$boomerang_url      = esc_url( Helpers::get_asset_url( 'js/boomr/boomerang-' . $boomerang_version . '.cutting-edge.min.js' ) );
		$delay_ms           = absint( $settings['delay_ms'] );
		$wait_enabled       = ! empty( $settings['wait_after_onload'] ) && '1' === $settings['wait_after_onload'];
		$strip_query_string = ! empty( $settings['strip_query_string'] ) && '1' === $settings['strip_query_string'];

		$boomr_mq = array(
			array( 'addVar', array( 'p_type' => $page_type ) ),
			array( 'addVar', array( 'p_gen' => 'wp' ) ),
		);

		if ( ! empty( $brum_site_id ) ) {
			$boomr_mq[] = array( 'addVar', array( 'brum_site_id' => $brum_site_id ) );
		}

		$js  = '(function(w) {' . "\n";
		$js .= '  if (!w) { return; }' . "\n";
		$js .= '  w.BOOMR = w.BOOMR || {};' . "\n";
		$js .= '  w.BOOMR.plugins = w.BOOMR.plugins || {};' . "\n";
		$js .= '  w.BOOMR_mq = w.BOOMR_mq || [];' . "\n";

		// Push variables to BOOMR_mq.
		foreach ( $boomr_mq as $entry ) {
			$js .= '  w.BOOMR_mq.push(["addVar", ' . wp_json_encode( $entry[1] ) . ']);' . "\n";
		}

		$js .= '  w.BOOMR.url = ' . wp_json_encode( $boomerang_url ) . ';' . "\n";

		// WaitAfterOnload plugin.
		if ( $wait_enabled && $delay_ms > 0 ) {
			$js .= "\n";
			$js .= '  w.BOOMR.plugins.WaitAfterOnload = {' . "\n";
			$js .= '    complete: false,' . "\n";
			$js .= '    init: function() {' . "\n";
			$js .= '      BOOMR.subscribe("page_ready", function() {' . "\n";
			$js .= '        setTimeout(function() {' . "\n";
			$js .= '          this.complete = true;' . "\n";
			$js .= '          BOOMR.sendBeacon();' . "\n";
			$js .= '        }.bind(this), ' . (int) $delay_ms . ');' . "\n";
			$js .= '      }, {}, this);' . "\n";
			$js .= '    },' . "\n";
			$js .= '    is_complete: function() {' . "\n";
			$js .= '      return this.complete;' . "\n";
			$js .= '    }' . "\n";
			$js .= '  };' . "\n";
		}

		// Boomerang configuration object.
		$config = array(
			'beacon_url'         => $beacon_url,
			'instrument_xhr'     => false,
			'strip_query_string' => $strip_query_string,
			'Continuity'         => array( 'enabled' => true ),
			'ResourceTiming'     => array(
				'enabled'     => true,
				'splitAtPath' => true,
			),
			'secure_cookie'      => true,
			'same_site_cookie'   => 'Strict',
		);

		$js .= "\n";
		$js .= '  w.basicRumBoomerangConfig = ' . wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . ';' . "\n";
		$js .= '})(window);';

		return $js;
	}

	/**
	 * Add data-cfasync="false" attribute to Basicrum script tags.
	 *
	 * @param string $tag    The full script tag.
	 * @param string $handle The script handle.
	 * @return string Modified tag.
	 */
	public function add_cfasync_attribute( $tag, $handle ) {
		if ( in_array( $handle, array( self::HANDLE_LOADER, self::HANDLE_CONFIG ), true ) ) {
			$tag = str_replace( '<script ', '<script data-cfasync="false" ', $tag );
		}

		return $tag;
	}
}
