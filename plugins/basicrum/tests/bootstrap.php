<?php
/**
 * Integration test bootstrap.
 *
 * @package Basicrum\Tests
 */

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WP test library if available.
$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	require_once $wp_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin for testing.
	 */
	function _manually_load_plugin() {
		require dirname( __DIR__ ) . '/basicrum.php';
	}

	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	require $wp_tests_dir . '/includes/bootstrap.php';
}
