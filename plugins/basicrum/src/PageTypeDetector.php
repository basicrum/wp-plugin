<?php
/**
 * Detects the current WordPress page type for beacon tagging.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PageTypeDetector class - maps WordPress conditional tags to
 * human-readable page type labels sent as `p_type` in Boomerang beacons.
 *
 * Reference: basicrum-magento-1 PageTypeDetector.php (27 page types)
 * and basicrum-magento-2 PageTypeDetector.php (12 page types).
 */
class PageTypeDetector {

	/**
	 * Detect the current page type.
	 *
	 * WooCommerce-specific types are checked first (more specific),
	 * then standard WordPress conditionals, with "unknown" as fallback.
	 *
	 * @return string The detected page type label.
	 */
	public function detect() {
		$page_type = $this->detect_woocommerce_page_type();

		if ( null === $page_type ) {
			$page_type = $this->detect_wordpress_page_type();
		}

		if ( null === $page_type ) {
			$page_type = 'unknown';
		}

		/**
		 * Filter the detected page type.
		 *
		 * Allows themes and plugins to override or extend page type detection.
		 *
		 * @param string $page_type The detected page type label.
		 */
		return apply_filters( 'basicrum_page_type', $page_type );
	}

	/**
	 * Detect WooCommerce-specific page types.
	 *
	 * Only runs when WooCommerce is active.
	 *
	 * @return string|null Page type or null if not a WooCommerce page.
	 */
	private function detect_woocommerce_page_type() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return null;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return 'checkout_success';
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 'checkout';
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			return 'product_category';
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 'shop';
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return 'account';
		}

		return null;
	}

	/**
	 * Detect standard WordPress page types.
	 *
	 * Checked in specificity order - most specific first.
	 *
	 * @return string|null Page type or null if undetectable.
	 */
	private function detect_wordpress_page_type() {
		if ( is_404() ) {
			return '404';
		}

		if ( is_search() ) {
			return 'search';
		}

		if ( is_front_page() ) {
			return 'home';
		}

		if ( is_single() ) {
			return 'post';
		}

		if ( is_page() ) {
			return 'page';
		}

		if ( is_category() ) {
			return 'category';
		}

		if ( is_tag() ) {
			return 'tag';
		}

		if ( is_author() ) {
			return 'author';
		}

		if ( is_date() ) {
			return 'date_archive';
		}

		if ( is_archive() ) {
			return 'archive';
		}

		return null;
	}
}
