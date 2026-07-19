<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Basicrum
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all plugin options.
delete_option( 'basicrum_settings' );

// Remove transients.
delete_transient( 'basicrum_notices' );
