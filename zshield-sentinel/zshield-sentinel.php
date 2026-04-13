<?php
/**
 * Plugin Name: ZShield Sentinel Security
 * Description: Lightweight security hardening, login guard, audit log, and file integrity checks.
 * Version: 1.1.0
 * Author: Swastik Infotech
 * Text Domain: zshield-sentinel
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ZSS_VERSION', '1.1.0');
define('ZSS_PLUGIN_FILE', __FILE__);
define('ZSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZSS_PLUGIN_URL', plugin_dir_url(__FILE__));

autoload();

function autoload() {
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-utils.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-audit-log.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-login-guard.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-2fa.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-file-integrity.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-malware-scan.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-security-score.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-admin.php';
    require_once ZSS_PLUGIN_DIR . 'includes/class-zss-plugin.php';
}

function zss_boot() {
    \ZSS\Plugin::instance();
}

zss_boot();

