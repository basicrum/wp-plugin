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
delete_option( 'basicrum_version' );

// Remove legacy option from PoC versions.
delete_option( 'basicrum_options' );

// Remove transients.
delete_transient( 'basicrum_notices' );
