<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(ZSS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(ZSS_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('init', array($this, 'register_login_rewrite'));

        add_action('update_option_zss_settings', array($this, 'log_settings_update'), 10, 2);
        add_action('zss_daily_prune', array($this, 'daily_prune'));
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        add_action('zss_scheduled_scan', array($this, 'run_scheduled_scan'));
        add_action('zss_malware_scheduled_scan', array($this, 'run_scheduled_malware_scan'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('zshield-sentinel', false, dirname(plugin_basename(ZSS_PLUGIN_FILE)) . '/languages');
    }

    public function activate() {
        if (!get_option('zss_settings')) {
            $defaults = array(
                'enable_login_guard' => '1',
                'max_attempts' => 5,
                'lockout_minutes' => 15,
                'login_allowlist' => '',
                'login_blocklist' => '',
                'enable_2fa' => '0',
                'enable_login_captcha' => '0',
                'captcha_type' => 'math',
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
                'enable_login_hardening' => '0',
                'login_access_key' => '',
                'login_custom_slug' => '',
                'enable_xmlrpc_disable' => '1',
                'disable_file_editor' => '1',
                'hide_wp_version' => '1',
                'enable_security_headers' => '1',
                'disable_pingbacks' => '1',
                'block_author_enum' => '1',
                'enable_firewall' => '0',
                'firewall_allowlist' => '',
                'firewall_blocklist' => '',
                'firewall_exclude_paths' => '',
                'enable_audit_log' => '1',
                'audit_log_retention_days' => 30,
                'enable_file_integrity' => '0',
                'enable_core_integrity' => '0',
                'email_lockout_alerts' => '0',
                'alert_email' => '',
                'enable_scheduled_scan' => '0',
                'scan_frequency' => 'weekly',
                'email_scan_reports' => '0',
                'scan_report_email' => '',
                'enable_malware_scheduled_scan' => '0',
                'malware_scan_frequency' => 'weekly',
                'email_malware_reports' => '0',
                'malware_report_email' => '',
                'uninstall_remove_data' => '0',
                'malware_allowlist_paths' => '',
                'malware_allowlist_rules' => '',
                'malware_ignore_vendor_js' => '1',
                'malware_scan_uploads' => '0',
                'malware_scan_mu_plugins' => '0',
                'access_role' => 'administrator',
            );
            add_option('zss_settings', $defaults);
        }

        Audit_Log::instance()->create_table();

        if (!wp_next_scheduled('zss_daily_prune')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zss_daily_prune');
        }

        $this->maybe_schedule_scan();
        $this->maybe_schedule_malware_scan();
        $this->register_login_rewrite();
        flush_rewrite_rules(false);
    }

    public function deactivate() {
        $timestamp = wp_next_scheduled('zss_daily_prune');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'zss_daily_prune');
        }

        $scan_timestamp = wp_next_scheduled('zss_scheduled_scan');
        if ($scan_timestamp) {
            wp_unschedule_event($scan_timestamp, 'zss_scheduled_scan');
        }

        $malware_timestamp = wp_next_scheduled('zss_malware_scheduled_scan');
        if ($malware_timestamp) {
            wp_unschedule_event($malware_timestamp, 'zss_malware_scheduled_scan');
        }
    }

    public function init() {
        Admin::instance()->register();
        Login_Guard::instance()->register();

        if (Utils::boolval(Utils::option('enable_login_hardening', '0'))) {
            add_action('login_init', array($this, 'enforce_login_hardening'));
            add_filter('login_url', array($this, 'filter_login_url'), 10, 3);
            add_filter('lostpassword_url', array($this, 'filter_lostpassword_url'), 10, 2);
            add_filter('query_vars', array($this, 'register_query_vars'));
        }

        if (Utils::boolval(Utils::option('enable_firewall', '0'))) {
            add_action('init', array($this, 'firewall_check'), 1);
        }

        if (Utils::boolval(Utils::option('enable_xmlrpc_disable', '1'))) {
            add_filter('xmlrpc_enabled', '__return_false');
        }

        if (Utils::boolval(Utils::option('disable_file_editor', '1'))) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }
            add_action('admin_menu', array($this, 'hide_editors'), 100);
        }

        if (Utils::boolval(Utils::option('hide_wp_version', '1'))) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        if (Utils::boolval(Utils::option('enable_security_headers', '1'))) {
            add_action('send_headers', array($this, 'send_security_headers'));
        }

        if (Utils::boolval(Utils::option('disable_pingbacks', '1'))) {
            add_filter('xmlrpc_methods', array($this, 'disable_pingback_methods'));
            add_filter('wp_headers', array($this, 'disable_pingback_header'));
        }

        if (Utils::boolval(Utils::option('block_author_enum', '1'))) {
            add_action('template_redirect', array($this, 'block_author_enum'));
        }

        $this->maybe_schedule_scan();
        $this->maybe_schedule_malware_scan();
    }

    public function admin_assets($hook) {
        if (strpos($hook, 'zss') === false) {
            return;
        }
        wp_enqueue_style('zss-admin', ZSS_PLUGIN_URL . 'assets/css/admin.css', array(), ZSS_VERSION);
        wp_enqueue_script('zss-admin', ZSS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ZSS_VERSION, true);
    }

    public function hide_editors() {
        remove_submenu_page('themes.php', 'theme-editor.php');
        remove_submenu_page('plugins.php', 'plugin-editor.php');
    }

    public function send_security_headers() {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    public function disable_pingback_methods($methods) {
        if (isset($methods['pingback.ping'])) {
            unset($methods['pingback.ping']);
        }
        return $methods;
    }

    public function disable_pingback_header($headers) {
        if (isset($headers['X-Pingback'])) {
            unset($headers['X-Pingback']);
        }
        return $headers;
    }

    public function block_author_enum() {
        if (is_admin()) {
            return;
        }
        if (isset($_GET['author']) && is_numeric($_GET['author'])) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    public function log_settings_update($old, $new) {
        Audit_Log::instance()->log('settings_update', 'Security settings updated.', get_current_user_id(), Utils::get_ip());
        $this->maybe_schedule_scan(true);
        $this->maybe_schedule_malware_scan(true);
        $this->maybe_flush_rewrite_on_settings($old, $new);
    }

    public function daily_prune() {
        $days = absint(Utils::option('audit_log_retention_days', 30));
        if ($days < 1) {
            $days = 30;
        }
        Audit_Log::instance()->prune($days);
    }

    public function add_cron_schedules($schedules) {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 7 * DAY_IN_SECONDS,
                'display' => __('Once Weekly', 'zshield-sentinel'),
            );
        }
        return $schedules;
    }

    private function maybe_schedule_scan($force_reschedule = false) {
        if (!Utils::boolval(Utils::option('enable_scheduled_scan', '0'))) {
            $scan_timestamp = wp_next_scheduled('zss_scheduled_scan');
            if ($scan_timestamp) {
                wp_unschedule_event($scan_timestamp, 'zss_scheduled_scan');
            }
            return;
        }

        $frequency = Utils::option('scan_frequency', 'weekly');
        if (!in_array($frequency, array('daily', 'weekly'), true)) {
            $frequency = 'weekly';
        }

        $existing = wp_next_scheduled('zss_scheduled_scan');
        if ($existing && !$force_reschedule) {
            return;
        }

        if ($existing) {
            wp_unschedule_event($existing, 'zss_scheduled_scan');
        }

        wp_schedule_event(time() + HOUR_IN_SECONDS, $frequency, 'zss_scheduled_scan');
    }

    private function maybe_schedule_malware_scan($force_reschedule = false) {
        if (!Utils::boolval(Utils::option('enable_malware_scheduled_scan', '0'))) {
            $scan_timestamp = wp_next_scheduled('zss_malware_scheduled_scan');
            if ($scan_timestamp) {
                wp_unschedule_event($scan_timestamp, 'zss_malware_scheduled_scan');
            }
            return;
        }

        $frequency = Utils::option('malware_scan_frequency', 'weekly');
        if (!in_array($frequency, array('daily', 'weekly'), true)) {
            $frequency = 'weekly';
        }

        $existing = wp_next_scheduled('zss_malware_scheduled_scan');
        if ($existing && !$force_reschedule) {
            return;
        }

        if ($existing) {
            wp_unschedule_event($existing, 'zss_malware_scheduled_scan');
        }

        wp_schedule_event(time() + HOUR_IN_SECONDS, $frequency, 'zss_malware_scheduled_scan');
    }

    public function run_scheduled_scan() {
        if (!Utils::boolval(Utils::option('enable_file_integrity', '0'))) {
            return;
        }

        $baseline = get_option('zss_file_integrity_baseline', array());
        if (empty($baseline) || empty($baseline['hashes'])) {
            Audit_Log::instance()->log('file_scan_skipped', 'Scheduled scan skipped: no baseline found.');
            return;
        }

        $result = File_Integrity::instance()->compare_to_baseline();

        if (Utils::boolval(Utils::option('email_scan_reports', '0'))) {
            $to = Utils::option('scan_report_email', '');
            if (!$to) {
                $to = get_option('admin_email');
            }
            $subject = __('ZShield Sentinel: File Integrity Scan Report', 'zshield-sentinel');
            $body = $this->build_scan_report($result);
            wp_mail($to, $subject, $body);
        }
    }

    public function run_scheduled_malware_scan() {
        $report = Malware_Scan::instance()->scan();

        if (Utils::boolval(Utils::option('email_malware_reports', '0'))) {
            $to = Utils::option('malware_report_email', '');
            if (!$to) {
                $to = get_option('admin_email');
            }
            $subject = __('ZShield Sentinel: Malware Scan Report', 'zshield-sentinel');
            $body = Malware_Scan::instance()->build_email_report($report);
            wp_mail($to, $subject, $body);
        }
    }

    private function build_scan_report($result) {
        $added = isset($result['added']) ? count($result['added']) : 0;
        $modified = isset($result['modified']) ? count($result['modified']) : 0;
        $removed = isset($result['removed']) ? count($result['removed']) : 0;

        $lines = array(
            'File Integrity Scan Report',
            'Scanned at: ' . (isset($result['scanned_at']) ? $result['scanned_at'] : Utils::now_mysql()),
            '',
            'Added: ' . $added,
            'Modified: ' . $modified,
            'Removed: ' . $removed,
            '',
            'Review full details in WordPress admin -> ZShield -> File Integrity.',
        );

        return implode("\n", $lines);
    }

    public function register_query_vars($vars) {
        $vars[] = 'zss_login';
        $vars[] = 'zss_key';
        return $vars;
    }

    public function register_login_rewrite() {
        $slug = Utils::option('login_custom_slug', '');
        if (!$slug) {
            return;
        }
        $slug = trim($slug);
        if ($slug === '') {
            return;
        }
        add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'wp-login.php?zss_login=1', 'top');
    }

    public function filter_login_url($url, $redirect = '', $force_reauth = false, $scheme = 'login') {
        if (!Utils::boolval(Utils::option('enable_login_hardening', '0'))) {
            return $url;
        }

        $slug = Utils::option('login_custom_slug', '');
        $key = Utils::option('login_access_key', '');

        if ($slug) {
            $url = home_url('/' . trim($slug, '/') . '/');
        } elseif ($key) {
            $url = add_query_arg('zss_key', rawurlencode($key), $url);
        }

        if (!empty($redirect)) {
            $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
        }

        if ($force_reauth) {
            $url = add_query_arg('reauth', '1', $url);
        }

        return $url;
    }

    public function filter_lostpassword_url($url, $redirect) {
        return $this->filter_login_url($url, $redirect, false, 'login');
    }

    public function enforce_login_hardening() {
        if (!Utils::boolval(Utils::option('enable_login_hardening', '0'))) {
            return;
        }

        if (is_user_logged_in()) {
            return;
        }

        $key = Utils::option('login_access_key', '');
        $passed_key = isset($_GET['zss_key']) ? sanitize_text_field(wp_unslash($_GET['zss_key'])) : '';
        $is_rewrite = isset($_GET['zss_login']) && $_GET['zss_login'] === '1';

        if ($is_rewrite) {
            return;
        }

        if (!$key && !Utils::option('login_custom_slug', '')) {
            return;
        }

        if ($key && hash_equals($key, $passed_key)) {
            return;
        }

        wp_die(__('Access to the login page is restricted.', 'zshield-sentinel'), __('Restricted', 'zshield-sentinel'), array('response' => 403));
    }

    private function maybe_flush_rewrite_on_settings($old, $new) {
        $old_slug = isset($old['login_custom_slug']) ? $old['login_custom_slug'] : '';
        $new_slug = isset($new['login_custom_slug']) ? $new['login_custom_slug'] : '';
        if ($old_slug !== $new_slug) {
            $this->register_login_rewrite();
            flush_rewrite_rules(false);
        }
    }

    public function firewall_check() {
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        if (!Utils::boolval(Utils::option('enable_firewall', '0'))) {
            return;
        }

        if (is_user_logged_in() && current_user_can(Utils::required_capability())) {
            return;
        }

        $ip = Utils::get_ip();
        $allowlist = Utils::option('firewall_allowlist', '');
        if ($ip && $allowlist && Utils::ip_in_list($ip, $allowlist)) {
            return;
        }

        $blocklist = Utils::option('firewall_blocklist', '');
        if ($ip && $blocklist && Utils::ip_in_list($ip, $blocklist)) {
            $this->firewall_block('Blocked IP address.', $ip);
        }

        $exclude = Utils::option('firewall_exclude_paths', '');
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if ($exclude && $uri) {
            $items = preg_split('/[\r\n,]+/', $exclude);
            foreach ($items as $item) {
                $item = trim($item);
                if ($item !== '' && strpos($uri, $item) !== false) {
                    return;
                }
            }
        }

        $query = isset($_SERVER['QUERY_STRING']) ? strtolower($_SERVER['QUERY_STRING']) : '';
        $request = strtolower(wp_json_encode($_REQUEST));

        $patterns = array(
            'base64_decode',
            'gzinflate',
            'eval(',
            'shell_exec',
            'passthru',
            'system(',
            'exec(',
            'php://input',
            'globals[',
            'wp-config.php',
            '../',
        );

        foreach ($patterns as $pattern) {
            if (($query && strpos($query, $pattern) !== false) || ($request && strpos($request, $pattern) !== false)) {
                $this->firewall_block('Blocked suspicious request pattern: ' . $pattern, $ip);
            }
        }
    }

    private function firewall_block($reason, $ip) {
        Audit_Log::instance()->log('firewall_block', $reason, null, $ip);
        wp_die(__('Request blocked by ZShield firewall.', 'zshield-sentinel'), __('Request Blocked', 'zshield-sentinel'), array('response' => 403));
    }
}

