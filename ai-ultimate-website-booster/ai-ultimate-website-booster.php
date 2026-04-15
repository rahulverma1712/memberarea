<?php
/**
 * Plugin Name: AI Ultimate Website Booster
 * Plugin URI: https://example.com/ai-ultimate-website-booster
 * Description: A professional AI toolkit for WordPress including content generation, schema, popup management, and health checks.
 * Version: 1.0.0
 * Author: Swastik Infotech
 * Author URI: https://swastikinfotech.com
 * Text Domain: ai-ultimate-website-booster
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AIWB_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIWB_URL', plugin_dir_url( __FILE__ ) );
define( 'AIWB_VERSION', '1.0.0' );

defined( 'AIWB_LOG_TABLE' ) || define( 'AIWB_LOG_TABLE', 'aiwb_action_logs' );

require_once AIWB_PATH . 'includes/class-aiwb.php';

register_activation_hook( __FILE__, array( 'AIWB_Main', 'activate' ) );
register_deactivation_hook( __FILE__, 'aiwb_deactivate' );
register_uninstall_hook( __FILE__, 'aiwb_uninstall' );

function aiwb_uninstall() {
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
    }

    global $wpdb;
    delete_option( 'aiwb_settings' );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aiwb_action_logs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aiwb_content_versions" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aiwb_health_scans" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aiwb_popups" );
}

function aiwb_deactivate() {
    $timestamp = wp_next_scheduled( 'aiwb_daily_automation' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'aiwb_daily_automation' );
    }
}

AIWB_Main::init();
