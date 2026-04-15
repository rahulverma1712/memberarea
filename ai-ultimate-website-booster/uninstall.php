<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

delete_option( 'aiwb_settings' );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aiwb_action_logs" );
