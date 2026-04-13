<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('zss_settings', array());
$remove_data = isset($settings['uninstall_remove_data']) && ($settings['uninstall_remove_data'] === '1' || $settings['uninstall_remove_data'] === 1);

if (!$remove_data) {
    return;
}

// Remove options.
delete_option('zss_settings');
delete_option('zss_file_integrity_baseline');
delete_option('zss_file_integrity_last_scan');
delete_option('zss_malware_last_scan');
delete_option('zss_malware_reports');
delete_option('zss_quarantine_map');

// Remove transients.
$transients = array();
if (function_exists('get_transient')) {
    // No easy way to find plugin transients without direct DB queries; leave as-is.
}

// Drop audit log table.
global $wpdb;
$table = $wpdb->prefix . 'zss_audit_log';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

