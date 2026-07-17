<?php
/**
 * WordPress privacy-policy integration.
 *
 * @package Basicrum
 */

namespace Basicrum\WP\Admin;

use Basicrum\WP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds transparent Basicrum disclosure text to the WordPress policy guide.
 */
class Privacy {

	/**
	 * Register the privacy-policy integration.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
	}

	/**
	 * Add editable suggested text to Settings > Privacy > Policy Guide.
	 *
	 * @return void
	 */
	public function add_policy_content() {
		$settings      = Helpers::get_settings();
		$beacon_url    = isset( $settings['beacon_url'] ) ? $settings['beacon_url'] : '';
		$is_configured = '1' === $settings['enabled'] && empty( Helpers::get_missing_required_settings( $settings ) );

		$content  = '<p class="privacy-policy-tutorial">';
		$content .= esc_html__( 'Review and edit this suggested text before publishing it. Confirm the configured collector, purposes, legal basis, recipients, and retention period for your site.', 'basicrum' );
		if ( ! $is_configured ) {
			$content .= '<br><strong>';
			$content .= esc_html__( 'Basicrum monitoring is currently inactive. Use the suggested text only if monitoring is enabled and fully configured.', 'basicrum' );
			$content .= '</strong>';
		}
		$content .= '</p>';
		$content .= '<p><strong>' . esc_html__( 'Suggested text:', 'basicrum' ) . '</strong></p>';
		$content .= '<p>';
		$content .= esc_html__( 'This site uses Basicrum to measure real-user performance. When monitoring runs, a visitor\'s browser sends page and resource URLs, performance and interaction timing metrics, page type, the configured site identifier, and technical browser, device, and network information to a performance collector. The collector also receives request information such as the IP address and user agent. Boomerang may use first-party cookies to maintain measurement state.', 'basicrum' );
		$content .= '</p>';

		if ( $beacon_url ) {
			$content .= '<p>';
			/* translators: %s: Configured Basicrum beacon URL. */
			$content .= sprintf( esc_html__( 'When monitoring runs, performance data is sent to the collector configured at %s.', 'basicrum' ), '<code>' . esc_html( $beacon_url ) . '</code>' );
			$content .= '</p>';
		}

		if ( $is_configured ) {
			$content .= '<p>';
		}
		if ( $is_configured && '1' === $settings['consent_enabled'] ) {
			$content .= esc_html__( 'Monitoring is configured to wait for the site\'s consent tool on every page. Basicrum does not persist consent across page loads or in its own cookie or server-side record. Signaling rejection, expiry, or withdrawal stops future browser collection but does not retract data already sent.', 'basicrum' );
		} elseif ( $is_configured ) {
			$content .= esc_html__( 'Monitoring is configured to start immediately on eligible pages without waiting for a consent signal.', 'basicrum' );
		}
		if ( $is_configured ) {
			$content .= '</p>';
		}

		wp_add_privacy_policy_content(
			esc_html__( 'Basicrum - Real User Monitoring', 'basicrum' ),
			wp_kses_post( $content )
		);
	}
}
