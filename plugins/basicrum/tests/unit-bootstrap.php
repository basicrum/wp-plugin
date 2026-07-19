<?php
/**
 * Unit test bootstrap.
 *
 * Defines WordPress constants and loads autoloader
 * so source files with ABSPATH guards don't exit.
 *
 * @package Basicrum\Tests
 */

// Define WordPress constants expected by source files.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'BASICRUM_VERSION' ) ) {
	define( 'BASICRUM_VERSION', '0.0.8' );
}

if ( ! defined( 'BASICRUM_PLUGIN_FILE' ) ) {
	define( 'BASICRUM_PLUGIN_FILE', dirname( __DIR__ ) . '/basicrum.php' );
}

if ( ! defined( 'BASICRUM_PLUGIN_DIR' ) ) {
	define( 'BASICRUM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
