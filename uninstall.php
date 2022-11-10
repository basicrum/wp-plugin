<?php // Clear the table for next RUM, captain.

if ( ! defined( 'WP_UNINSTALL_PLUGIN') ){
    exit;
}

delete_option('basicrum_options');