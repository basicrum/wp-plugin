<?php
/**
 * Compatibility with caching and optimization plugins.
 *
 * @package Basicrum
 */

namespace Basicrum\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility class — excludes Basicrum scripts from optimization by
 * popular caching and performance plugins.
 *
 * Modeled after wordpress-plausible/src/Compatibility.php
 */
class Compatibility {

	/**
	 * Constructor — register exclusion filters.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Register all compatibility filters.
	 *
	 * @return void
	 */
	private function init() {
		// WP Rocket.
		add_filter( 'rocket_exclude_js', array( $this, 'exclude_js' ) );
		add_filter( 'rocket_exclude_defer_js', array( $this, 'exclude_js' ) );
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'exclude_js' ) );

		// Autoptimize.
		add_filter( 'autoptimize_filter_js_exclude', array( $this, 'autoptimize_exclude_js' ) );

		// LiteSpeed Cache.
		add_filter( 'litespeed_optimize_js_excludes', array( $this, 'exclude_js' ) );

		// SG Optimizer.
		add_filter( 'sgo_js_minify_exclude', array( $this, 'exclude_js' ) );
		add_filter( 'sgo_javascript_combine_exclude', array( $this, 'exclude_js' ) );

		// W3 Total Cache.
		add_filter( 'w3tc_minify_js_do_tag_minification', array( $this, 'w3tc_exclude' ), 10, 3 );

		// WP Optimize.
		add_filter( 'wp-optimize-minify-default-exclusions', array( $this, 'exclude_js' ) );
	}

	/**
	 * Add boomerang script URL to exclusion arrays.
	 *
	 * @param array $exclusions Current exclusion list.
	 * @return array
	 */
	public function exclude_js( $exclusions ) {
		if ( ! is_array( $exclusions ) ) {
			$exclusions = array();
		}

		$exclusions[] = 'boomerang';
		$exclusions[] = 'basicrum';
		$exclusions[] = 'boomr';

		return $exclusions;
	}

	/**
	 * Add to Autoptimize's comma-separated exclusion string.
	 *
	 * @param string $exclusions Comma-separated exclusion string.
	 * @return string
	 */
	public function autoptimize_exclude_js( $exclusions ) {
		if ( ! empty( $exclusions ) ) {
			$exclusions .= ',';
		}

		$exclusions .= 'boomerang,basicrum,boomr';

		return $exclusions;
	}

	/**
	 * Prevent W3 Total Cache from minifying Basicrum scripts.
	 *
	 * @param bool   $do_minify Whether to minify.
	 * @param string $url       Script URL.
	 * @param string $tag       Full script tag.
	 * @return bool
	 */
	public function w3tc_exclude( $do_minify, $url, $tag ) {
		if ( false !== strpos( $url, 'boomerang' ) || false !== strpos( $url, 'basicrum' ) ) {
			return false;
		}

		return $do_minify;
	}
}
