<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'maybe_redirect_report'));
        add_action('admin_post_zss_create_baseline', array($this, 'handle_create_baseline'));
        add_action('admin_post_zss_run_scan', array($this, 'handle_run_scan'));
        add_action('admin_post_zss_run_malware_scan', array($this, 'handle_run_malware_scan'));
        add_action('admin_post_zss_export_audit_log', array($this, 'handle_export_audit_log'));
        add_action('admin_post_zss_export_settings', array($this, 'handle_export_settings'));
        add_action('admin_post_zss_import_settings', array($this, 'handle_import_settings'));
        add_action('admin_post_zss_quarantine_file', array($this, 'handle_quarantine_file'));
        add_action('admin_post_zss_restore_file', array($this, 'handle_restore_file'));
        add_action('show_user_profile', array($this, 'render_2fa_profile'));
        add_action('edit_user_profile', array($this, 'render_2fa_profile'));
        add_action('personal_options_update', array($this, 'save_2fa_profile'));
        add_action('edit_user_profile_update', array($this, 'save_2fa_profile'));
    }

    public function register_menu() {
        $cap = Utils::required_capability();
        add_menu_page(
            __('ZShield', 'zshield-sentinel'),
            __('ZShield', 'zshield-sentinel'),
            $cap,
            'zss-settings',
            array($this, 'render_dashboard_page'),
            'dashicons-shield',
            80
        );

        add_submenu_page(
            'zss-settings',
            __('Dashboard', 'zshield-sentinel'),
            __('Dashboard', 'zshield-sentinel'),
            $cap,
            'zss-settings',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'zss-settings',
            __('Settings', 'zshield-sentinel'),
            __('Settings', 'zshield-sentinel'),
            $cap,
            'zss-settings-config',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'zss-settings',
            __('Documentation', 'zshield-sentinel'),
            __('Documentation', 'zshield-sentinel'),
            $cap,
            'zss-documentation',
            array($this, 'render_docs_page')
        );

        add_submenu_page(
            'zss-settings',
            __('Audit Log', 'zshield-sentinel'),
            __('Audit Log', 'zshield-sentinel'),
            $cap,
            'zss-audit-log',
            array($this, 'render_audit_log_page')
        );

        add_submenu_page(
            'zss-settings',
            __('File Integrity', 'zshield-sentinel'),
            __('File Integrity', 'zshield-sentinel'),
            $cap,
            'zss-file-integrity',
            array($this, 'render_file_integrity_page')
        );

        add_submenu_page(
            'zss-settings',
            __('Malware Scan', 'zshield-sentinel'),
            __('Malware Scan', 'zshield-sentinel'),
            $cap,
            'zss-malware-scan',
            array($this, 'render_malware_scan_page')
        );

        // Hidden page for viewing a single scan report.
        add_submenu_page(
            null,
            __('Scan Report', 'zshield-sentinel'),
            __('Scan Report', 'zshield-sentinel'),
            $cap,
            'zss-scan-report',
            array($this, 'render_scan_report_page')
        );

        // Hidden page for viewing a single file detail from a report.
        add_submenu_page(
            null,
            __('File Details', 'zshield-sentinel'),
            __('File Details', 'zshield-sentinel'),
            $cap,
            'zss-file-details',
            array($this, 'render_file_details_page')
        );
    }

    public function register_settings() {
        register_setting('zss_settings_group', 'zss_settings', array($this, 'sanitize_settings'));

        add_settings_section('zss_section_main', __('Security Settings', 'zshield-sentinel'), '__return_false', 'zss-settings');

        add_settings_field('enable_login_guard', __('Login Guard', 'zshield-sentinel'), array($this, 'field_enable_login_guard'), 'zss-settings', 'zss_section_main');
        add_settings_field('max_attempts', __('Max Attempts', 'zshield-sentinel'), array($this, 'field_max_attempts'), 'zss-settings', 'zss_section_main');
        add_settings_field('lockout_minutes', __('Lockout Minutes', 'zshield-sentinel'), array($this, 'field_lockout_minutes'), 'zss-settings', 'zss_section_main');
        add_settings_field('login_allowlist', __('Login IP Allowlist', 'zshield-sentinel'), array($this, 'field_login_allowlist'), 'zss-settings', 'zss_section_main');
        add_settings_field('login_blocklist', __('Login IP Blocklist', 'zshield-sentinel'), array($this, 'field_login_blocklist'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_2fa', __('Two-Factor Authentication', 'zshield-sentinel'), array($this, 'field_enable_2fa'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_login_captcha', __('Login Captcha', 'zshield-sentinel'), array($this, 'field_enable_login_captcha'), 'zss-settings', 'zss_section_main');
        add_settings_field('captcha_type', __('Captcha Type', 'zshield-sentinel'), array($this, 'field_captcha_type'), 'zss-settings', 'zss_section_main');
        add_settings_field('recaptcha_site_key', __('reCAPTCHA Site Key', 'zshield-sentinel'), array($this, 'field_recaptcha_site_key'), 'zss-settings', 'zss_section_main');
        add_settings_field('recaptcha_secret_key', __('reCAPTCHA Secret Key', 'zshield-sentinel'), array($this, 'field_recaptcha_secret_key'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_login_hardening', __('Login URL Hardening', 'zshield-sentinel'), array($this, 'field_enable_login_hardening'), 'zss-settings', 'zss_section_main');
        add_settings_field('login_access_key', __('Login Access Key', 'zshield-sentinel'), array($this, 'field_login_access_key'), 'zss-settings', 'zss_section_main');
        add_settings_field('login_custom_slug', __('Custom Login Slug', 'zshield-sentinel'), array($this, 'field_login_custom_slug'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_xmlrpc_disable', __('Disable XML-RPC', 'zshield-sentinel'), array($this, 'field_enable_xmlrpc_disable'), 'zss-settings', 'zss_section_main');
        add_settings_field('disable_file_editor', __('Disable File Editor', 'zshield-sentinel'), array($this, 'field_disable_file_editor'), 'zss-settings', 'zss_section_main');
        add_settings_field('hide_wp_version', __('Hide WordPress Version', 'zshield-sentinel'), array($this, 'field_hide_wp_version'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_security_headers', __('Security Headers', 'zshield-sentinel'), array($this, 'field_enable_security_headers'), 'zss-settings', 'zss_section_main');
        add_settings_field('disable_pingbacks', __('Disable Pingbacks', 'zshield-sentinel'), array($this, 'field_disable_pingbacks'), 'zss-settings', 'zss_section_main');
        add_settings_field('block_author_enum', __('Block Author Enumeration', 'zshield-sentinel'), array($this, 'field_block_author_enum'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_firewall', __('Application Firewall', 'zshield-sentinel'), array($this, 'field_enable_firewall'), 'zss-settings', 'zss_section_main');
        add_settings_field('firewall_allowlist', __('Firewall IP Allowlist', 'zshield-sentinel'), array($this, 'field_firewall_allowlist'), 'zss-settings', 'zss_section_main');
        add_settings_field('firewall_blocklist', __('Firewall IP Blocklist', 'zshield-sentinel'), array($this, 'field_firewall_blocklist'), 'zss-settings', 'zss_section_main');
        add_settings_field('firewall_exclude_paths', __('Firewall Exclude Paths', 'zshield-sentinel'), array($this, 'field_firewall_exclude_paths'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_audit_log', __('Audit Log', 'zshield-sentinel'), array($this, 'field_enable_audit_log'), 'zss-settings', 'zss_section_main');
        add_settings_field('audit_log_retention_days', __('Audit Log Retention (days)', 'zshield-sentinel'), array($this, 'field_audit_log_retention_days'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_file_integrity', __('File Integrity', 'zshield-sentinel'), array($this, 'field_enable_file_integrity'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_core_integrity', __('Core File Integrity', 'zshield-sentinel'), array($this, 'field_enable_core_integrity'), 'zss-settings', 'zss_section_main');
        add_settings_field('email_lockout_alerts', __('Email Alerts', 'zshield-sentinel'), array($this, 'field_email_lockout_alerts'), 'zss-settings', 'zss_section_main');
        add_settings_field('alert_email', __('Alert Email', 'zshield-sentinel'), array($this, 'field_alert_email'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_scheduled_scan', __('Scheduled Scan', 'zshield-sentinel'), array($this, 'field_enable_scheduled_scan'), 'zss-settings', 'zss_section_main');
        add_settings_field('scan_frequency', __('Scan Frequency', 'zshield-sentinel'), array($this, 'field_scan_frequency'), 'zss-settings', 'zss_section_main');
        add_settings_field('email_scan_reports', __('Scan Email Reports', 'zshield-sentinel'), array($this, 'field_email_scan_reports'), 'zss-settings', 'zss_section_main');
        add_settings_field('scan_report_email', __('Scan Report Email', 'zshield-sentinel'), array($this, 'field_scan_report_email'), 'zss-settings', 'zss_section_main');
        add_settings_field('enable_malware_scheduled_scan', __('Scheduled Malware Scan', 'zshield-sentinel'), array($this, 'field_enable_malware_scheduled_scan'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_scan_frequency', __('Malware Scan Frequency', 'zshield-sentinel'), array($this, 'field_malware_scan_frequency'), 'zss-settings', 'zss_section_main');
        add_settings_field('email_malware_reports', __('Malware Email Reports', 'zshield-sentinel'), array($this, 'field_email_malware_reports'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_report_email', __('Malware Report Email', 'zshield-sentinel'), array($this, 'field_malware_report_email'), 'zss-settings', 'zss_section_main');
        add_settings_field('uninstall_remove_data', __('Remove Data on Uninstall', 'zshield-sentinel'), array($this, 'field_uninstall_remove_data'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_ignore_vendor_js', __('Ignore Vendor JS', 'zshield-sentinel'), array($this, 'field_malware_ignore_vendor_js'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_scan_uploads', __('Scan Uploads Folder', 'zshield-sentinel'), array($this, 'field_malware_scan_uploads'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_scan_mu_plugins', __('Scan MU-Plugins', 'zshield-sentinel'), array($this, 'field_malware_scan_mu_plugins'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_allowlist_rules', __('Malware Rule Allowlist', 'zshield-sentinel'), array($this, 'field_malware_allowlist_rules'), 'zss-settings', 'zss_section_main');
        add_settings_field('malware_allowlist_paths', __('Malware Path Allowlist', 'zshield-sentinel'), array($this, 'field_malware_allowlist_paths'), 'zss-settings', 'zss_section_main');
        add_settings_field('access_role', __('Minimum Access Role', 'zshield-sentinel'), array($this, 'field_access_role'), 'zss-settings', 'zss_section_main');
    }

    public function sanitize_settings($input) {
        $output = array();

        $output['enable_login_guard'] = !empty($input['enable_login_guard']) ? '1' : '0';
        $output['max_attempts'] = isset($input['max_attempts']) ? absint($input['max_attempts']) : 5;
        $output['lockout_minutes'] = isset($input['lockout_minutes']) ? absint($input['lockout_minutes']) : 15;
        $output['login_allowlist'] = isset($input['login_allowlist']) ? sanitize_textarea_field($input['login_allowlist']) : '';
        $output['login_blocklist'] = isset($input['login_blocklist']) ? sanitize_textarea_field($input['login_blocklist']) : '';
        $output['enable_2fa'] = !empty($input['enable_2fa']) ? '1' : '0';
        $output['enable_login_captcha'] = !empty($input['enable_login_captcha']) ? '1' : '0';
        $captcha_type = isset($input['captcha_type']) ? sanitize_text_field($input['captcha_type']) : 'math';
        if (!in_array($captcha_type, array('math', 'recaptcha'), true)) {
            $captcha_type = 'math';
        }
        $output['captcha_type'] = $captcha_type;
        $output['recaptcha_site_key'] = isset($input['recaptcha_site_key']) ? sanitize_text_field($input['recaptcha_site_key']) : '';
        $output['recaptcha_secret_key'] = isset($input['recaptcha_secret_key']) ? sanitize_text_field($input['recaptcha_secret_key']) : '';
        $output['enable_login_hardening'] = !empty($input['enable_login_hardening']) ? '1' : '0';
        $output['login_access_key'] = isset($input['login_access_key']) ? sanitize_text_field($input['login_access_key']) : '';
        $output['login_custom_slug'] = isset($input['login_custom_slug']) ? sanitize_title($input['login_custom_slug']) : '';
        $output['enable_xmlrpc_disable'] = !empty($input['enable_xmlrpc_disable']) ? '1' : '0';
        $output['disable_file_editor'] = !empty($input['disable_file_editor']) ? '1' : '0';
        $output['hide_wp_version'] = !empty($input['hide_wp_version']) ? '1' : '0';
        $output['enable_security_headers'] = !empty($input['enable_security_headers']) ? '1' : '0';
        $output['disable_pingbacks'] = !empty($input['disable_pingbacks']) ? '1' : '0';
        $output['block_author_enum'] = !empty($input['block_author_enum']) ? '1' : '0';
        $output['enable_firewall'] = !empty($input['enable_firewall']) ? '1' : '0';
        $output['firewall_allowlist'] = isset($input['firewall_allowlist']) ? sanitize_textarea_field($input['firewall_allowlist']) : '';
        $output['firewall_blocklist'] = isset($input['firewall_blocklist']) ? sanitize_textarea_field($input['firewall_blocklist']) : '';
        $output['firewall_exclude_paths'] = isset($input['firewall_exclude_paths']) ? sanitize_textarea_field($input['firewall_exclude_paths']) : '';
        $output['enable_audit_log'] = !empty($input['enable_audit_log']) ? '1' : '0';
        $output['audit_log_retention_days'] = isset($input['audit_log_retention_days']) ? absint($input['audit_log_retention_days']) : 30;
        $output['enable_file_integrity'] = !empty($input['enable_file_integrity']) ? '1' : '0';
        $output['enable_core_integrity'] = !empty($input['enable_core_integrity']) ? '1' : '0';
        $output['email_lockout_alerts'] = !empty($input['email_lockout_alerts']) ? '1' : '0';
        $output['alert_email'] = isset($input['alert_email']) ? sanitize_email($input['alert_email']) : '';
        $output['enable_scheduled_scan'] = !empty($input['enable_scheduled_scan']) ? '1' : '0';
        $frequency = isset($input['scan_frequency']) ? sanitize_text_field($input['scan_frequency']) : 'weekly';
        if (!in_array($frequency, array('daily', 'weekly'), true)) {
            $frequency = 'weekly';
        }
        $output['scan_frequency'] = $frequency;
        $output['email_scan_reports'] = !empty($input['email_scan_reports']) ? '1' : '0';
        $output['scan_report_email'] = isset($input['scan_report_email']) ? sanitize_email($input['scan_report_email']) : '';
        $output['enable_malware_scheduled_scan'] = !empty($input['enable_malware_scheduled_scan']) ? '1' : '0';
        $malware_frequency = isset($input['malware_scan_frequency']) ? sanitize_text_field($input['malware_scan_frequency']) : 'weekly';
        if (!in_array($malware_frequency, array('daily', 'weekly'), true)) {
            $malware_frequency = 'weekly';
        }
        $output['malware_scan_frequency'] = $malware_frequency;
        $output['email_malware_reports'] = !empty($input['email_malware_reports']) ? '1' : '0';
        $output['malware_report_email'] = isset($input['malware_report_email']) ? sanitize_email($input['malware_report_email']) : '';
        $output['uninstall_remove_data'] = !empty($input['uninstall_remove_data']) ? '1' : '0';
        $output['malware_ignore_vendor_js'] = !empty($input['malware_ignore_vendor_js']) ? '1' : '0';
        $output['malware_scan_uploads'] = !empty($input['malware_scan_uploads']) ? '1' : '0';
        $output['malware_scan_mu_plugins'] = !empty($input['malware_scan_mu_plugins']) ? '1' : '0';
        $output['malware_allowlist_rules'] = isset($input['malware_allowlist_rules']) ? sanitize_text_field($input['malware_allowlist_rules']) : '';
        $output['malware_allowlist_paths'] = isset($input['malware_allowlist_paths']) ? sanitize_textarea_field($input['malware_allowlist_paths']) : '';
        $role = isset($input['access_role']) ? sanitize_text_field($input['access_role']) : 'administrator';
        if (!in_array($role, array('administrator', 'editor', 'author', 'contributor'), true)) {
            $role = 'administrator';
        }
        $output['access_role'] = $role;

        return $output;
    }

    private function checkbox($name, $label) {
        $value = Utils::option($name, '0');
        $checked = $value === '1' ? 'checked' : '';
        printf('<label><input type="checkbox" name="zss_settings[%s]" value="1" %s> %s</label>', esc_attr($name), $checked, esc_html($label));
    }

    public function field_enable_login_guard() {
        $this->checkbox('enable_login_guard', __('Enable login attempt throttling', 'zshield-sentinel'));
    }

    public function field_max_attempts() {
        $value = Utils::option('max_attempts', 5);
        printf('<input type="number" min="1" name="zss_settings[max_attempts]" value="%d" class="small-text" />', esc_attr($value));
    }

    public function field_lockout_minutes() {
        $value = Utils::option('lockout_minutes', 15);
        printf('<input type="number" min="1" name="zss_settings[lockout_minutes]" value="%d" class="small-text" />', esc_attr($value));
    }

    public function field_login_allowlist() {
        $value = Utils::option('login_allowlist', '');
        echo '<textarea name="zss_settings[login_allowlist]" rows="3" class="large-text code" placeholder="192.168.1.10&#10;10.0.0.0/24&#10;203.0.113.*">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('One IP or CIDR per line. If set, only these IPs can log in.', 'zshield-sentinel') . '</p>';
    }

    public function field_login_blocklist() {
        $value = Utils::option('login_blocklist', '');
        echo '<textarea name="zss_settings[login_blocklist]" rows="3" class="large-text code" placeholder="198.51.100.4&#10;203.0.113.0/24&#10;192.0.2.*">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Blocklisted IPs will be denied before authentication.', 'zshield-sentinel') . '</p>';
    }

    public function field_enable_2fa() {
        $this->checkbox('enable_2fa', __('Enable 2FA (TOTP) for users who opt-in in their profile.', 'zshield-sentinel'));
    }

    public function field_enable_login_captcha() {
        $this->checkbox('enable_login_captcha', __('Require captcha on login form.', 'zshield-sentinel'));
    }

    public function field_captcha_type() {
        $value = Utils::option('captcha_type', 'math');
        $options = array(
            'math' => __('Math Challenge', 'zshield-sentinel'),
            'recaptcha' => __('Google reCAPTCHA v2', 'zshield-sentinel'),
        );
        echo '<select name="zss_settings[captcha_type]">';
        foreach ($options as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
        }
        echo '</select>';
    }

    public function field_recaptcha_site_key() {
        $value = Utils::option('recaptcha_site_key', '');
        echo '<input type="text" name="zss_settings[recaptcha_site_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    public function field_recaptcha_secret_key() {
        $value = Utils::option('recaptcha_secret_key', '');
        echo '<input type="text" name="zss_settings[recaptcha_secret_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    public function field_enable_login_hardening() {
        $this->checkbox('enable_login_hardening', __('Restrict access to wp-login.php with a key or custom slug.', 'zshield-sentinel'));
    }

    public function field_login_access_key() {
        $value = Utils::option('login_access_key', '');
        echo '<input type="text" name="zss_settings[login_access_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('If set, login will be available at wp-login.php?zss_key=YOURKEY', 'zshield-sentinel') . '</p>';
    }

    public function field_login_custom_slug() {
        $value = Utils::option('login_custom_slug', '');
        echo '<input type="text" name="zss_settings[login_custom_slug]" value="' . esc_attr($value) . '" class="regular-text" placeholder="zshield-login" />';
        echo '<p class="description">' . esc_html__('Adds a custom login URL like https://example.com/zshield-login/. Leave empty to disable.', 'zshield-sentinel') . '</p>';
    }

    public function field_enable_xmlrpc_disable() {
        $this->checkbox('enable_xmlrpc_disable', __('Disable XML-RPC for better attack surface reduction', 'zshield-sentinel'));
    }

    public function field_disable_file_editor() {
        $this->checkbox('disable_file_editor', __('Hide theme and plugin editors in admin', 'zshield-sentinel'));
    }

    public function field_hide_wp_version() {
        $this->checkbox('hide_wp_version', __('Remove WordPress version from page source', 'zshield-sentinel'));
    }

    public function field_enable_security_headers() {
        $this->checkbox('enable_security_headers', __('Add basic security headers', 'zshield-sentinel'));
    }

    public function field_disable_pingbacks() {
        $this->checkbox('disable_pingbacks', __('Disable pingbacks and trackbacks', 'zshield-sentinel'));
    }

    public function field_block_author_enum() {
        $this->checkbox('block_author_enum', __('Block ?author= requests to reduce user enumeration', 'zshield-sentinel'));
    }

    public function field_enable_firewall() {
        $this->checkbox('enable_firewall', __('Enable application firewall for common exploit patterns.', 'zshield-sentinel'));
    }

    public function field_firewall_allowlist() {
        $value = Utils::option('firewall_allowlist', '');
        echo '<textarea name="zss_settings[firewall_allowlist]" rows="3" class="large-text code" placeholder="192.168.1.10&#10;10.0.0.0/24">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Allowlisted IPs bypass firewall rules.', 'zshield-sentinel') . '</p>';
    }

    public function field_firewall_blocklist() {
        $value = Utils::option('firewall_blocklist', '');
        echo '<textarea name="zss_settings[firewall_blocklist]" rows="3" class="large-text code" placeholder="203.0.113.0/24">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Blocklisted IPs are always denied.', 'zshield-sentinel') . '</p>';
    }

    public function field_firewall_exclude_paths() {
        $value = Utils::option('firewall_exclude_paths', '');
        echo '<textarea name="zss_settings[firewall_exclude_paths]" rows="3" class="large-text code" placeholder="/wp-admin/admin-ajax.php">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Paths to exclude from firewall checks (one per line).', 'zshield-sentinel') . '</p>';
    }

    public function field_enable_audit_log() {
        $this->checkbox('enable_audit_log', __('Track security events in audit log', 'zshield-sentinel'));
    }

    public function field_audit_log_retention_days() {
        $value = Utils::option('audit_log_retention_days', 30);
        printf('<input type="number" min="1" name="zss_settings[audit_log_retention_days]" value="%d" class="small-text" />', esc_attr($value));
    }

    public function field_enable_file_integrity() {
        $this->checkbox('enable_file_integrity', __('Enable file integrity scans for plugins/themes', 'zshield-sentinel'));
    }

    public function field_enable_core_integrity() {
        $this->checkbox('enable_core_integrity', __('Include WordPress core files in integrity scan', 'zshield-sentinel'));
    }

    public function field_email_lockout_alerts() {
        $this->checkbox('email_lockout_alerts', __('Email admin when IP is locked out', 'zshield-sentinel'));
    }

    public function field_alert_email() {
        $value = Utils::option('alert_email', '');
        printf('<input type="email" name="zss_settings[alert_email]" value="%s" class="regular-text" placeholder="%s" />', esc_attr($value), esc_attr(get_option('admin_email')));
        echo '<p class="description">' . esc_html__('Leave empty to use the site admin email.', 'zshield-sentinel') . '</p>';
    }

    public function field_enable_scheduled_scan() {
        $this->checkbox('enable_scheduled_scan', __('Run file integrity scans automatically', 'zshield-sentinel'));
    }

    public function field_scan_frequency() {
        $value = Utils::option('scan_frequency', 'weekly');
        $options = array(
            'daily' => __('Daily', 'zshield-sentinel'),
            'weekly' => __('Weekly', 'zshield-sentinel'),
        );
        echo '<select name="zss_settings[scan_frequency]">';
        foreach ($options as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
        }
        echo '</select>';
    }

    public function field_email_scan_reports() {
        $this->checkbox('email_scan_reports', __('Email scan report after each scan', 'zshield-sentinel'));
    }

    public function field_scan_report_email() {
        $value = Utils::option('scan_report_email', '');
        printf('<input type="email" name="zss_settings[scan_report_email]" value="%s" class="regular-text" placeholder="%s" />', esc_attr($value), esc_attr(get_option('admin_email')));
        echo '<p class="description">' . esc_html__('Leave empty to use the site admin email.', 'zshield-sentinel') . '</p>';
    }

    public function field_enable_malware_scheduled_scan() {
        $this->checkbox('enable_malware_scheduled_scan', __('Run malware scans automatically', 'zshield-sentinel'));
    }

    public function field_malware_scan_frequency() {
        $value = Utils::option('malware_scan_frequency', 'weekly');
        $options = array(
            'daily' => __('Daily', 'zshield-sentinel'),
            'weekly' => __('Weekly', 'zshield-sentinel'),
        );
        echo '<select name="zss_settings[malware_scan_frequency]">';
        foreach ($options as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
        }
        echo '</select>';
    }

    public function field_email_malware_reports() {
        $this->checkbox('email_malware_reports', __('Email malware report after each scan', 'zshield-sentinel'));
    }

    public function field_malware_report_email() {
        $value = Utils::option('malware_report_email', '');
        printf('<input type="email" name="zss_settings[malware_report_email]" value="%s" class="regular-text" placeholder="%s" />', esc_attr($value), esc_attr(get_option('admin_email')));
        echo '<p class="description">' . esc_html__('Leave empty to use the site admin email.', 'zshield-sentinel') . '</p>';
    }

    public function field_uninstall_remove_data() {
        $this->checkbox('uninstall_remove_data', __('Delete all data when plugin is uninstalled', 'zshield-sentinel'));
    }

    public function field_malware_ignore_vendor_js() {
        $this->checkbox('malware_ignore_vendor_js', __('Ignore common vendor/minified JS files', 'zshield-sentinel'));
        echo '<p class="description">' . esc_html__('Skips JS files that are likely vendor bundles (e.g., *.min.js, vendor, dist, build).', 'zshield-sentinel') . '</p>';
    }

    public function field_malware_scan_uploads() {
        $this->checkbox('malware_scan_uploads', __('Include wp-content/uploads in malware scan', 'zshield-sentinel'));
        echo '<p class="description">' . esc_html__('Scans the uploads folder for suspicious patterns. Can increase scan time on large sites.', 'zshield-sentinel') . '</p>';
    }

    public function field_malware_scan_mu_plugins() {
        $this->checkbox('malware_scan_mu_plugins', __('Include mu-plugins in malware scan', 'zshield-sentinel'));
        echo '<p class="description">' . esc_html__('Includes must-use plugins for malware scanning.', 'zshield-sentinel') . '</p>';
    }

    public function field_malware_allowlist_rules() {
        $value = Utils::option('malware_allowlist_rules', '');
        echo '<input type="text" name="zss_settings[malware_allowlist_rules]" value="' . esc_attr($value) . '" class="regular-text" placeholder="eval, assert, system_exec" />';
        echo '<p class="description">' . esc_html__('Comma-separated rule keys to ignore. Available keys: eval, base64_decode, gzinflate, rot13, preg_replace_e, system_exec, php_write, remote_request, assert, concat.', 'zshield-sentinel') . '</p>';
    }

    public function field_malware_allowlist_paths() {
        $value = Utils::option('malware_allowlist_paths', '');
        echo '<textarea name="zss_settings[malware_allowlist_paths]" rows="4" class="large-text code" placeholder="wp-content/plugins/your-plugin/\\nwp-content/themes/your-theme/">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('One path per line. Matching paths will be skipped during malware scan.', 'zshield-sentinel') . '</p>';
    }

    public function field_access_role() {
        $value = Utils::option('access_role', 'administrator');
        $options = array(
            'administrator' => __('Administrator', 'zshield-sentinel'),
            'editor' => __('Editor', 'zshield-sentinel'),
            'author' => __('Author', 'zshield-sentinel'),
            'contributor' => __('Contributor', 'zshield-sentinel'),
        );
        echo '<select name="zss_settings[access_role]">';
        foreach ($options as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Minimum role allowed to access ZShield pages.', 'zshield-sentinel') . '</p>';
    }

    public function handle_create_baseline() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_create_baseline');

        File_Integrity::instance()->build_baseline();
        wp_safe_redirect(admin_url('admin.php?page=zss-file-integrity&zss_notice=baseline'));
        exit;
    }

    public function handle_run_scan() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_run_scan');

        File_Integrity::instance()->compare_to_baseline();
        wp_safe_redirect(admin_url('admin.php?page=zss-file-integrity&zss_notice=scan'));
        exit;
    }

    public function handle_run_malware_scan() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_run_malware_scan');

        Malware_Scan::instance()->scan();
        wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=scan'));
        exit;
    }

    public function handle_export_audit_log() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_export_audit_log');

        $limit = isset($_POST['limit']) ? absint(wp_unslash($_POST['limit'])) : 500;
        if ($limit < 1) {
            $limit = 500;
        }
        if ($limit > 2000) {
            $limit = 2000;
        }

        $logs = Audit_Log::instance()->get_logs($limit);

        $filename = 'zshield-audit-log-' . gmdate('Ymd-His') . '.csv';
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output) {
            fputcsv($output, array('Time', 'Event', 'Event Type', 'Message', 'User', 'IP'));
            foreach ($logs as $log) {
                $user = $log['user_id'] ? get_user_by('id', (int) $log['user_id']) : null;
                $message = wp_strip_all_tags($log['message']);
                fputcsv(
                    $output,
                    array(
                        $log['created_at'],
                        $this->format_event_label($log['event_type']),
                        $log['event_type'],
                        $message,
                        $user ? $user->user_login : '-',
                        $log['ip_address'] ? $log['ip_address'] : '-',
                    )
                );
            }
            fclose($output);
        }
        exit;
    }

    public function handle_export_settings() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_export_settings');

        $settings = get_option('zss_settings', array());
        $payload = array(
            'exported_at' => Utils::now_mysql(),
            'plugin' => 'zshield-sentinel',
            'settings' => $settings,
        );

        $filename = 'zshield-settings-' . gmdate('Ymd-His') . '.json';
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo wp_json_encode($payload, JSON_PRETTY_PRINT);
        exit;
    }

    public function handle_import_settings() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_import_settings');

        if (empty($_FILES['zss_settings_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=zss-settings-config&zss_notice=import_failed'));
            exit;
        }

        $raw = file_get_contents($_FILES['zss_settings_file']['tmp_name']);
        if ($raw === false) {
            wp_safe_redirect(admin_url('admin.php?page=zss-settings-config&zss_notice=import_failed'));
            exit;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            wp_safe_redirect(admin_url('admin.php?page=zss-settings-config&zss_notice=import_failed'));
            exit;
        }

        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : $data;
        $sanitized = $this->sanitize_settings($settings);
        update_option('zss_settings', $sanitized);

        wp_safe_redirect(admin_url('admin.php?page=zss-settings-config&zss_notice=imported'));
        exit;
    }

    public function handle_quarantine_file() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_quarantine_file');

        $path = isset($_GET['file']) ? rawurldecode(sanitize_text_field(wp_unslash($_GET['file']))) : '';
        if (!$path) {
            wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=quarantine_failed'));
            exit;
        }

        $result = Malware_Scan::instance()->quarantine_file($path);
        if (!empty($result['error'])) {
            wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=quarantine_failed'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=quarantined'));
        exit;
    }

    public function handle_restore_file() {
        if (!current_user_can(Utils::required_capability())) {
            wp_die(__('Insufficient permissions.', 'zshield-sentinel'));
        }
        check_admin_referer('zss_restore_file');

        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        if (!$id) {
            wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=restore_failed'));
            exit;
        }

        $result = Malware_Scan::instance()->restore_quarantine($id);
        if (!empty($result['error'])) {
            wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=restore_failed'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=zss-malware-scan&zss_notice=restored'));
        exit;
    }

    public function render_2fa_profile($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        if (!Utils::boolval(Utils::option('enable_2fa', '0'))) {
            return;
        }

        $enabled = Two_Factor::instance()->is_enabled($user->ID);
        $secret = Two_Factor::instance()->get_secret($user->ID);
        $uri = Two_Factor::instance()->get_provisioning_uri($user);

        echo '<h2>' . esc_html__('ZShield Two-Factor Authentication', 'zshield-sentinel') . '</h2>';
        echo '<table class="form-table" role="presentation">';
        echo '<tr>';
        echo '<th><label for="zss_2fa_enabled">' . esc_html__('Enable 2FA', 'zshield-sentinel') . '</label></th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="zss_2fa_enabled" value="1" ' . checked($enabled, true, false) . ' /> ' . esc_html__('Require a 6-digit code at login.', 'zshield-sentinel') . '</label>';
        echo '<p class="description">' . esc_html__('Use Google Authenticator, Authy, or any TOTP app.', 'zshield-sentinel') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . esc_html__('Secret Key', 'zshield-sentinel') . '</th>';
        echo '<td>';
        echo '<input type="text" class="regular-text" readonly value="' . esc_attr($secret) . '" />';
        echo '<p class="description">' . esc_html__('Add this secret to your authenticator app.', 'zshield-sentinel') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . esc_html__('Authenticator URI', 'zshield-sentinel') . '</th>';
        echo '<td>';
        echo '<input type="text" class="large-text" readonly value="' . esc_attr($uri) . '" />';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . esc_html__('Regenerate Secret', 'zshield-sentinel') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="zss_2fa_regen" value="1" /> ' . esc_html__('Generate a new secret on save', 'zshield-sentinel') . '</label>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    public function save_2fa_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (!Utils::boolval(Utils::option('enable_2fa', '0'))) {
            return;
        }

        $enabled = isset($_POST['zss_2fa_enabled']) ? '1' : '0';
        update_user_meta($user_id, 'zss_2fa_enabled', $enabled);

        if (!empty($_POST['zss_2fa_regen'])) {
            Two_Factor::instance()->regenerate_secret($user_id);
        } else {
            Two_Factor::instance()->get_secret($user_id);
        }
    }

    private function render_shell_start($title, $subtitle, $active_page, $actions = array(), $extra_classes = array()) {
        $classes = array('wrap', 'zss-page');
        if (is_array($extra_classes)) {
            foreach ($extra_classes as $class) {
                $classes[] = sanitize_html_class($class);
            }
        }
        $class_attr = implode(' ', $classes);
        $logo_url = esc_url(ZSS_PLUGIN_URL . 'assets/images/zshield-logo.png');
        $nav_items = array(
            'zss-settings' => __('Dashboard', 'zshield-sentinel'),
            'zss-settings-config' => __('Settings', 'zshield-sentinel'),
            'zss-audit-log' => __('Audit Log', 'zshield-sentinel'),
            'zss-file-integrity' => __('File Integrity', 'zshield-sentinel'),
            'zss-malware-scan' => __('Malware Scan', 'zshield-sentinel'),
            'zss-documentation' => __('Documentation', 'zshield-sentinel'),
        );

        echo '<div class="' . esc_attr($class_attr) . '">';
        echo '<div class="zss-shell">';
        echo '<div class="zss-topbar">';
        echo '<div class="zss-brand">';
        echo '<img class="zss-brand-logo" src="' . $logo_url . '" alt="' . esc_attr__('ZShield', 'zshield-sentinel') . '" />';
        echo '<div class="zss-brand-copy">';
        echo '<span class="zss-brand-title">' . esc_html__('ZShield Sentinel', 'zshield-sentinel') . '</span>';
        echo '<span class="zss-brand-sub">' . esc_html__('Sentinel Security Console', 'zshield-sentinel') . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="zss-top-actions">';
        echo '<span class="zss-top-badge">' . esc_html__('Version', 'zshield-sentinel') . ' ' . esc_html(ZSS_VERSION) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<nav class="zss-subnav">';
        foreach ($nav_items as $slug => $label) {
            $class = $slug === $active_page ? 'zss-subnav-link is-active' : 'zss-subnav-link';
            $url = admin_url('admin.php?page=' . $slug);
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        echo '<div class="zss-page-header">';
        echo '<div>';
        echo '<h1 class="zss-page-title">' . esc_html($title) . '</h1>';
        if (!empty($subtitle)) {
            echo '<p class="zss-page-subtitle">' . esc_html($subtitle) . '</p>';
        }
        echo '</div>';
        if (!empty($actions)) {
            echo '<div class="zss-header-actions">';
            foreach ($actions as $action) {
                $label = isset($action['label']) ? $action['label'] : '';
                $url = isset($action['url']) ? $action['url'] : '';
                $primary = !empty($action['primary']);
                $btn_class = $primary ? 'button button-primary' : 'button';
                echo '<a class="' . esc_attr($btn_class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="zss-content">';
    }

    private function render_shell_end() {
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_dashboard_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $score = Security_Score::instance()->get_score();
        $baseline = get_option('zss_file_integrity_baseline', array());
        $last_scan = get_option('zss_file_integrity_last_scan', array());
        $malware = get_option('zss_malware_last_scan', array());

        $baseline_time = isset($baseline['created_at']) ? $baseline['created_at'] : __('Not yet', 'zshield-sentinel');
        $scan_time = isset($last_scan['scanned_at']) ? $last_scan['scanned_at'] : __('Not yet', 'zshield-sentinel');
        $malware_time = isset($malware['scanned_at']) ? $malware['scanned_at'] : __('Not yet', 'zshield-sentinel');

        $added = isset($last_scan['added']) ? count($last_scan['added']) : 0;
        $modified = isset($last_scan['modified']) ? count($last_scan['modified']) : 0;
        $removed = isset($last_scan['removed']) ? count($last_scan['removed']) : 0;

        $malware_risk = isset($malware['risk_score']) ? absint($malware['risk_score']) : 0;
        $malware_findings = isset($malware['total_findings']) ? absint($malware['total_findings']) : 0;

        $actions = array(
            array(
                'label' => __('Open Settings', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-settings-config'),
                'primary' => true,
            ),
            array(
                'label' => __('File Integrity', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-file-integrity'),
            ),
            array(
                'label' => __('Malware Scan', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-malware-scan'),
            ),
        );
        $this->render_shell_start(
            __('Dashboard', 'zshield-sentinel'),
            __('Security hardening, monitoring, and scanning in one place.', 'zshield-sentinel'),
            'zss-settings',
            $actions,
            array('zss-dashboard')
        );

        echo '<div class="zss-card zss-quick-panel">';
        echo '<div class="zss-quick-header">';
        echo '<div>';
        echo '<h3>' . esc_html__('Quick Actions', 'zshield-sentinel') . '</h3>';
        echo '<p class="description">' . esc_html__('Jump to common actions without scrolling.', 'zshield-sentinel') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="zss-quick-actions">';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=zss-settings-config')) . '">' . esc_html__('Configure Settings', 'zshield-sentinel') . '</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=zss-audit-log')) . '">' . esc_html__('View Audit Log', 'zshield-sentinel') . '</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=zss-file-integrity')) . '">' . esc_html__('Run File Scan', 'zshield-sentinel') . '</a>';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=zss-malware-scan')) . '">' . esc_html__('Run Malware Scan', 'zshield-sentinel') . '</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="zss-grid">';
        echo '<div class="zss-card zss-score-card ' . esc_attr($score['class']) . '">';
        echo '<div class="zss-score-ring" style="--zss-score:' . esc_attr($score['score']) . ';">';
        echo '<div class="zss-score-value">' . esc_html($score['score']) . '<span>/100</span></div>';
        echo '</div>';
        echo '<div class="zss-score-meta">';
        echo '<h3>' . esc_html__('Security Score', 'zshield-sentinel') . '</h3>';
        echo '<p class="zss-score-grade">' . esc_html($score['label']) . '</p>';
        if (!empty($score['tips'])) {
            echo '<ul class="zss-tip-list">';
            foreach ($score['tips'] as $tip) {
                echo '<li>' . esc_html($tip) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('File Integrity', 'zshield-sentinel') . '</h3>';
        echo '<p class="zss-meta"><strong>' . esc_html__('Baseline:', 'zshield-sentinel') . '</strong> ' . esc_html($baseline_time) . '</p>';
        echo '<p class="zss-meta"><strong>' . esc_html__('Last scan:', 'zshield-sentinel') . '</strong> ' . esc_html($scan_time) . '</p>';
        echo '<div class="zss-inline-stats">';
        echo '<span>' . esc_html(sprintf(__('Added: %d', 'zshield-sentinel'), $added)) . '</span>';
        echo '<span>' . esc_html(sprintf(__('Modified: %d', 'zshield-sentinel'), $modified)) . '</span>';
        echo '<span>' . esc_html(sprintf(__('Removed: %d', 'zshield-sentinel'), $removed)) . '</span>';
        echo '</div>';
        echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=zss-file-integrity')) . '">' . esc_html__('Review File Scan', 'zshield-sentinel') . '</a>';
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Malware Scan', 'zshield-sentinel') . '</h3>';
        echo '<p class="zss-meta"><strong>' . esc_html__('Last scan:', 'zshield-sentinel') . '</strong> ' . esc_html($malware_time) . '</p>';
        echo '<p class="zss-meta"><strong>' . esc_html__('Risk score:', 'zshield-sentinel') . '</strong> ' . esc_html($malware_risk) . '</p>';
        echo '<p class="zss-meta"><strong>' . esc_html__('Findings:', 'zshield-sentinel') . '</strong> ' . esc_html($malware_findings) . '</p>';
        echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=zss-malware-scan')) . '">' . esc_html__('Open Malware Scan', 'zshield-sentinel') . '</a>';
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Protection Status', 'zshield-sentinel') . '</h3>';
        echo '<ul class="zss-status-list">';
        $this->render_status_item(__('Login Guard', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_login_guard', '1')));
        $this->render_status_item(__('Login CAPTCHA', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_login_captcha', '0')));
        $this->render_status_item(__('Login Hardening', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_login_hardening', '0')));
        $this->render_status_item(__('XML-RPC Disabled', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_xmlrpc_disable', '1')));
        $this->render_status_item(__('Security Headers', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_security_headers', '1')));
        $this->render_status_item(__('Pingbacks Disabled', 'zshield-sentinel'), Utils::boolval(Utils::option('disable_pingbacks', '1')));
        $this->render_status_item(__('Author Enumeration Block', 'zshield-sentinel'), Utils::boolval(Utils::option('block_author_enum', '1')));
        $this->render_status_item(__('Firewall', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_firewall', '0')));
        $this->render_status_item(__('Audit Log', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_audit_log', '1')));
        $this->render_status_item(__('File Integrity', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_file_integrity', '0')));
        $this->render_status_item(__('Scheduled Scan', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_scheduled_scan', '0')));
        $this->render_status_item(__('Malware Scheduled Scan', 'zshield-sentinel'), Utils::boolval(Utils::option('enable_malware_scheduled_scan', '0')));
        echo '</ul>';
        echo '</div>';

        echo '</div>';
        $this->render_shell_end();
    }

    public function render_malware_scan_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $notice = isset($_GET['zss_notice']) ? sanitize_text_field(wp_unslash($_GET['zss_notice'])) : '';
        if ($notice === 'scan') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Malware scan completed.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'quarantined') {
            echo '<div class="notice notice-success"><p>' . esc_html__('File moved to quarantine.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'restored') {
            echo '<div class="notice notice-success"><p>' . esc_html__('File restored from quarantine.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'quarantine_failed') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Unable to quarantine the file.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'restore_failed') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Unable to restore the file.', 'zshield-sentinel') . '</p></div>';
        }

        $report = get_option('zss_malware_last_scan', array());
        $findings = isset($report['findings']) && is_array($report['findings']) ? $report['findings'] : array();

        $scan_time = isset($report['scanned_at']) ? $report['scanned_at'] : __('Not yet', 'zshield-sentinel');
        $files_scanned = isset($report['files_scanned']) ? absint($report['files_scanned']) : 0;
        $suspicious_files = isset($report['suspicious_files']) ? absint($report['suspicious_files']) : 0;
        $risk_score = isset($report['risk_score']) ? absint($report['risk_score']) : 0;
        $duration = isset($report['duration']) ? $report['duration'] : '';

        $this->render_shell_start(
            __('Malware Scan', 'zshield-sentinel'),
            __('Heuristic scan for suspicious patterns in plugin and theme files.', 'zshield-sentinel'),
            'zss-malware-scan'
        );

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Last Scan Summary', 'zshield-sentinel') . '</h3>';
        echo '<p><strong>' . esc_html__('Scanned at:', 'zshield-sentinel') . '</strong> ' . esc_html($scan_time) . '</p>';
        echo '<p><strong>' . esc_html__('Files scanned:', 'zshield-sentinel') . '</strong> ' . esc_html($files_scanned) . '</p>';
        echo '<p><strong>' . esc_html__('Suspicious files:', 'zshield-sentinel') . '</strong> ' . esc_html($suspicious_files) . '</p>';
        echo '<p><strong>' . esc_html__('Risk score:', 'zshield-sentinel') . '</strong> ' . esc_html($risk_score) . '</p>';
        if ($duration !== '') {
            echo '<p><strong>' . esc_html__('Duration:', 'zshield-sentinel') . '</strong> ' . esc_html($duration) . 's</p>';
        }
        echo '</div>';

        echo '<div class="zss-card zss-filter-bar">';
        echo '<label for="zss-malware-filter">' . esc_html__('Severity', 'zshield-sentinel') . '</label> ';
        echo '<select id="zss-malware-filter">';
        echo '<option value="all">' . esc_html__('All', 'zshield-sentinel') . '</option>';
        echo '<option value="high">' . esc_html__('High', 'zshield-sentinel') . '</option>';
        echo '<option value="medium">' . esc_html__('Medium', 'zshield-sentinel') . '</option>';
        echo '<option value="low">' . esc_html__('Low', 'zshield-sentinel') . '</option>';
        echo '</select> ';
        echo '<label for="zss-malware-search">' . esc_html__('Search', 'zshield-sentinel') . '</label> ';
        echo '<input type="search" id="zss-malware-search" placeholder="' . esc_attr__('Search file path or rule...', 'zshield-sentinel') . '" />';
        echo '<span class="zss-filter-count" id="zss-malware-count"></span>';
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Findings', 'zshield-sentinel') . '</h3>';
        echo '<table class="widefat striped zss-table" id="zss-malware-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Severity', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Rule', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('File', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Line', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Excerpt', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Suggestion', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Actions', 'zshield-sentinel') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($findings)) {
            echo '<tr><td colspan="7">' . esc_html__('No suspicious patterns detected.', 'zshield-sentinel') . '</td></tr>';
        } else {
            foreach ($findings as $finding) {
                $severity = isset($finding['severity']) ? absint($finding['severity']) : 1;
                $severity_label = $this->severity_label($severity);
                $severity_class = $this->severity_class($severity);
                $is_false_positive = !empty($finding['false_positive']);
                $path = isset($finding['file']) ? $finding['file'] : '';
                $rule = isset($finding['rule']) ? $finding['rule'] : '';
                $line = isset($finding['line']) ? absint($finding['line']) : 0;
                $excerpt = isset($finding['excerpt']) ? $finding['excerpt'] : '';
                $suggestion = $this->malware_suggestion($finding);

                echo '<tr data-severity="' . esc_attr($severity_label) . '" data-path="' . esc_attr(strtolower($path . ' ' . $rule)) . '">';
                echo '<td><span class="zss-risk-badge ' . esc_attr($severity_class) . '">' . esc_html(ucfirst($severity_label)) . '</span>';
                if ($is_false_positive) {
                    echo ' <span class="zss-fp-badge">' . esc_html__('False Positive', 'zshield-sentinel') . '</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($rule) . '</td>';
                echo '<td>' . esc_html($this->format_scan_path($path)) . '</td>';
                echo '<td>' . esc_html($line) . '</td>';
                $actions = '-';
                $quarantine = Malware_Scan::instance()->is_quarantined($path);
                if ($quarantine && isset($quarantine['id'])) {
                    $restore_url = add_query_arg(
                        array(
                            'action' => 'zss_restore_file',
                            'id' => $quarantine['id'],
                        ),
                        admin_url('admin-post.php')
                    );
                    $restore_url = wp_nonce_url($restore_url, 'zss_restore_file');
                    $actions = '<a class="button button-small" href="' . esc_url($restore_url) . '">' . esc_html__('Restore', 'zshield-sentinel') . '</a>';
                } elseif ($path) {
                    $quarantine_url = add_query_arg(
                        array(
                            'action' => 'zss_quarantine_file',
                            'file' => rawurlencode($path),
                        ),
                        admin_url('admin-post.php')
                    );
                    $quarantine_url = wp_nonce_url($quarantine_url, 'zss_quarantine_file');
                    $actions = '<a class="button button-small" href="' . esc_url($quarantine_url) . '">' . esc_html__('Quarantine', 'zshield-sentinel') . '</a>';
                }

                echo '<td class="zss-excerpt">' . esc_html($excerpt) . '</td>';
                echo '<td class="zss-excerpt">' . wp_kses($suggestion, $this->docs_allowed_html()) . '</td>';
                echo '<td>' . $actions . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '<p class="description">' . esc_html__('This is a heuristic scan. Review findings before taking action.', 'zshield-sentinel') . '</p>';
        echo '</div>';

        $quarantine_map = Malware_Scan::instance()->get_quarantine_map();
        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Quarantine', 'zshield-sentinel') . '</h3>';
        if (empty($quarantine_map)) {
            echo '<p>' . esc_html__('No files are currently quarantined.', 'zshield-sentinel') . '</p>';
        } else {
            echo '<table class="widefat striped zss-table">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('File', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Quarantined At', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Actions', 'zshield-sentinel') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($quarantine_map as $entry) {
                $restore_url = add_query_arg(
                    array(
                        'action' => 'zss_restore_file',
                        'id' => isset($entry['id']) ? $entry['id'] : '',
                    ),
                    admin_url('admin-post.php')
                );
                $restore_url = wp_nonce_url($restore_url, 'zss_restore_file');
                echo '<tr>';
                echo '<td>' . esc_html($this->format_scan_path(isset($entry['original']) ? $entry['original'] : '')) . '</td>';
                echo '<td>' . esc_html(isset($entry['time']) ? $entry['time'] : '-') . '</td>';
                echo '<td><a class="button button-small" href="' . esc_url($restore_url) . '">' . esc_html__('Restore', 'zshield-sentinel') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Actions', 'zshield-sentinel') . '</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('zss_run_malware_scan');
        echo '<input type="hidden" name="action" value="zss_run_malware_scan" />';
        submit_button(__('Run Malware Scan', 'zshield-sentinel'), 'primary');
        echo '</form>';
        echo '</div>';

        $this->render_shell_end();
    }

    public function render_settings_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $notice = isset($_GET['zss_notice']) ? sanitize_text_field(wp_unslash($_GET['zss_notice'])) : '';
        if ($notice === 'baseline') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Baseline created.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'scan') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Scan completed.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'imported') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings imported successfully.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'import_failed') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Settings import failed. Please upload a valid JSON file.', 'zshield-sentinel') . '</p></div>';
        }

        $this->render_shell_start(
            __('Security Settings', 'zshield-sentinel'),
            __('Tune hardening, monitoring, and alert preferences.', 'zshield-sentinel'),
            'zss-settings-config'
        );
        echo '<div class="zss-card zss-card--full">';
        echo '<form method="post" action="options.php" class="zss-settings-form">';
        wp_nonce_field('zss_settings_save');
        settings_fields('zss_settings_group');
        do_settings_sections('zss-settings');
        submit_button();
        echo '</form>';
        echo '</div>';

        echo '<div class="zss-card zss-card--full">';
        echo '<h3>' . esc_html__('Settings Import/Export', 'zshield-sentinel') . '</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="zss-inline-form">';
        wp_nonce_field('zss_export_settings');
        echo '<input type="hidden" name="action" value="zss_export_settings" />';
        submit_button(__('Export Settings (JSON)', 'zshield-sentinel'), 'secondary', 'submit', false);
        echo '</form>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data" class="zss-inline-form">';
        wp_nonce_field('zss_import_settings');
        echo '<input type="hidden" name="action" value="zss_import_settings" />';
        echo '<input type="file" name="zss_settings_file" accept="application/json" required />';
        submit_button(__('Import Settings', 'zshield-sentinel'), 'secondary', 'submit', false);
        echo '</form>';
        echo '</div>';

        $this->render_shell_end();
    }

    public function render_docs_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $doc_path = ZSS_PLUGIN_DIR . 'docs/zshield-docs.html';
        $doc_html = '';

        if (file_exists($doc_path)) {
            $doc_html = file_get_contents($doc_path);
        }

        $replacements = array(
            '{{dashboard_url}}' => admin_url('admin.php?page=zss-settings'),
            '{{settings_url}}' => admin_url('admin.php?page=zss-settings-config'),
            '{{audit_url}}' => admin_url('admin.php?page=zss-audit-log'),
            '{{integrity_url}}' => admin_url('admin.php?page=zss-file-integrity'),
            '{{malware_url}}' => admin_url('admin.php?page=zss-malware-scan'),
        );

        if (!empty($doc_html)) {
            $doc_html = strtr($doc_html, $replacements);
            $doc_html = wp_kses($doc_html, $this->docs_allowed_html());
        }

        $actions = array(
            array(
                'label' => __('Open Settings', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-settings-config'),
                'primary' => true,
            ),
            array(
                'label' => __('Go to Dashboard', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-settings'),
            ),
        );
        $this->render_shell_start(
            __('Documentation', 'zshield-sentinel'),
            __('Everything you need to understand and operate ZShield Sentinel.', 'zshield-sentinel'),
            'zss-documentation',
            $actions,
            array('zss-docs-page')
        );

        if (empty($doc_html)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Documentation file is missing. Please verify docs/zshield-docs.html exists.', 'zshield-sentinel') . '</p></div>';
        } else {
            echo $doc_html;
        }

        $this->render_shell_end();
    }

    private function docs_allowed_html() {
        $allowed = array(
            'div' => array('class' => true, 'id' => true, 'aria-label' => true, 'role' => true),
            'section' => array('class' => true, 'id' => true, 'aria-label' => true, 'role' => true),
            'header' => array('class' => true, 'id' => true),
            'footer' => array('class' => true, 'id' => true),
            'h1' => array('class' => true, 'id' => true),
            'h2' => array('class' => true, 'id' => true),
            'h3' => array('class' => true, 'id' => true),
            'h4' => array('class' => true, 'id' => true),
            'p' => array('class' => true, 'id' => true),
            'ul' => array('class' => true, 'id' => true),
            'ol' => array('class' => true, 'id' => true),
            'li' => array('class' => true, 'id' => true),
            'span' => array('class' => true, 'id' => true),
            'strong' => array('class' => true, 'id' => true),
            'em' => array('class' => true, 'id' => true),
            'small' => array('class' => true, 'id' => true),
            'code' => array('class' => true, 'id' => true),
            'pre' => array('class' => true, 'id' => true),
            'a' => array('class' => true, 'href' => true, 'target' => true, 'rel' => true, 'aria-label' => true),
            'hr' => array('class' => true),
            'br' => array(),
            'table' => array('class' => true),
            'thead' => array('class' => true),
            'tbody' => array('class' => true),
            'tr' => array('class' => true),
            'th' => array('class' => true, 'scope' => true, 'colspan' => true, 'rowspan' => true),
            'td' => array('class' => true, 'colspan' => true, 'rowspan' => true),
            'figure' => array('class' => true),
            'figcaption' => array('class' => true),
        );

        return $allowed;
    }

    private function render_scan_list($title, $items) {
        echo '<h4>' . esc_html($title) . '</h4>';
        if (empty($items)) {
            echo '<p>' . esc_html__('None', 'zshield-sentinel') . '</p>';
            return;
        }
        echo '<ul>';
        foreach ($items as $item) {
            echo '<li>' . esc_html($this->format_scan_path($item)) . '</li>';
        }
        echo '</ul>';
    }

    private function format_scan_path($path) {
        $normalized = str_replace('\\', '/', $path);
        $content_dir = str_replace('\\', '/', WP_CONTENT_DIR);
        if (strpos($normalized, $content_dir) === 0) {
            $relative = substr($normalized, strlen($content_dir));
            return 'wp-content' . $relative;
        }
        return $normalized;
    }

    private function render_status_item($label, $enabled) {
        $status = $enabled ? __('Enabled', 'zshield-sentinel') : __('Disabled', 'zshield-sentinel');
        $status_class = $enabled ? 'zss-status-on' : 'zss-status-off';
        echo '<li><strong>' . esc_html($label) . ':</strong> <span class="' . esc_attr($status_class) . '">' . esc_html($status) . '</span></li>';
    }

    public function render_audit_log_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $logs = Audit_Log::instance()->get_logs(50);

        $this->render_shell_start(
            __('Audit Log', 'zshield-sentinel'),
            __('Recent security events and system activity.', 'zshield-sentinel'),
            'zss-audit-log'
        );
        echo '<div class="zss-card">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="zss-inline-form">';
        wp_nonce_field('zss_export_audit_log');
        echo '<input type="hidden" name="action" value="zss_export_audit_log" />';
        echo '<input type="hidden" name="limit" value="500" />';
        submit_button(__('Export CSV', 'zshield-sentinel'), 'secondary', 'submit', false);
        echo '</form>';
        echo '<h3>' . esc_html__('Recent Events', 'zshield-sentinel') . '</h3>';
        echo '<table class="widefat striped zss-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Time', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Event', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Message', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Report', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('User', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('IP', 'zshield-sentinel') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($logs)) {
            echo '<tr><td colspan="6">' . esc_html__('No logs yet.', 'zshield-sentinel') . '</td></tr>';
        } else {
            foreach ($logs as $log) {
                $user = $log['user_id'] ? get_user_by('id', (int) $log['user_id']) : null;
                $report_link = '-';
                if ($log['event_type'] === 'file_scan') {
                    $report_id = $this->extract_report_id($log['message']);
                    if ($report_id) {
                        $url = admin_url('admin.php?page=zss-scan-report&report=' . rawurlencode($report_id));
                        $report_link = '<a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('View Report', 'zshield-sentinel') . '</a>';
                    }
                }
                echo '<tr>';
                echo '<td>' . esc_html($log['created_at']) . '</td>';
                echo '<td><span class="zss-event-badge zss-event-' . esc_attr($log['event_type']) . '">' . esc_html($this->format_event_label($log['event_type'])) . '</span></td>';
                echo '<td>' . esc_html($log['message']) . '</td>';
                echo '<td>' . $report_link . '</td>';
                echo '<td>' . esc_html($user ? $user->user_login : '-') . '</td>';
                echo '<td>' . esc_html($log['ip_address'] ? $log['ip_address'] : '-') . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
        $this->render_shell_end();
    }

    public function render_file_integrity_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $baseline = get_option('zss_file_integrity_baseline', array());
        $baseline_files = isset($baseline['files']) && is_array($baseline['files']) ? $baseline['files'] : array();
        $last_scan = get_option('zss_file_integrity_last_scan', array());
        $reports = get_option('zss_scan_reports', array());
        if (!is_array($reports)) {
            $reports = array();
        }
        $active_report = $last_scan;
        $report_id = isset($_GET['report']) ? sanitize_text_field(wp_unslash($_GET['report'])) : '';

        $notice = isset($_GET['zss_notice']) ? sanitize_text_field(wp_unslash($_GET['zss_notice'])) : '';
        if ($notice === 'baseline') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Baseline created.', 'zshield-sentinel') . '</p></div>';
        } elseif ($notice === 'scan') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Scan completed.', 'zshield-sentinel') . '</p></div>';
        }

        $this->render_shell_start(
            __('File Integrity', 'zshield-sentinel'),
            __('Track changes in plugin and theme files. Create a baseline after a clean install, then run scans to detect added, modified, or removed files.', 'zshield-sentinel'),
            'zss-file-integrity'
        );

        $baseline_time = isset($baseline['created_at']) ? $baseline['created_at'] : __('Not yet', 'zshield-sentinel');
        $scan_time = isset($active_report['scanned_at']) ? $active_report['scanned_at'] : __('Not yet', 'zshield-sentinel');

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Status', 'zshield-sentinel') . '</h3>';
        echo '<p><strong>' . esc_html__('Baseline created:', 'zshield-sentinel') . '</strong> ' . esc_html($baseline_time) . '</p>';
        echo '<p><strong>' . esc_html__('Last scan:', 'zshield-sentinel') . '</strong> ' . esc_html($scan_time) . '</p>';
        echo '</div>';

        if ($report_id && !$active_report) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Report not found. Please run a new scan to generate fresh reports.', 'zshield-sentinel') . '</p></div>';
        }

        if (!empty($active_report)) {
            $added = isset($active_report['added']) ? $active_report['added'] : array();
            $modified = isset($active_report['modified']) ? $active_report['modified'] : array();
            $removed = isset($active_report['removed']) ? $active_report['removed'] : array();

            echo '<div class="zss-card">';
            echo '<h3>' . esc_html__('Scan Summary', 'zshield-sentinel') . '</h3>';
            echo '<p>' . esc_html(sprintf('Added: %d | Modified: %d | Removed: %d', count($added), count($modified), count($removed))) . '</p>';
            echo '</div>';

            if (!empty($active_report['report_id'])) {
                echo '<p class="description">' . esc_html(sprintf('Report ID: %s', $active_report['report_id'])) . '</p>';
            }

            echo '<div class="zss-card zss-filter-bar">';
            echo '<label for="zss-filter-type">' . esc_html__('Filter', 'zshield-sentinel') . '</label> ';
            echo '<select id="zss-filter-type">';
            echo '<option value="all">' . esc_html__('All', 'zshield-sentinel') . '</option>';
            echo '<option value="added">' . esc_html__('Added', 'zshield-sentinel') . '</option>';
            echo '<option value="modified">' . esc_html__('Modified', 'zshield-sentinel') . '</option>';
            echo '<option value="removed">' . esc_html__('Removed', 'zshield-sentinel') . '</option>';
            echo '</select> ';
            echo '<label for="zss-filter-search">' . esc_html__('Search', 'zshield-sentinel') . '</label> ';
            echo '<input type="search" id="zss-filter-search" placeholder="' . esc_attr__('Search file path...', 'zshield-sentinel') . '" />';
            echo '<span class="zss-filter-count" id="zss-filter-count"></span>';
            echo '</div>';

            echo '<h2>' . esc_html__('Scan Results (Details)', 'zshield-sentinel') . '</h2>';
            echo '<table class="widefat striped zss-table" id="zss-scan-table">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Type', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('File Path', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Actions', 'zshield-sentinel') . '</th>';
            echo '</tr></thead><tbody>';

            $rows = $this->build_scan_rows($added, $modified, $removed);
            foreach ($rows as $row) {
                $actions = '-';
                if ($row['type'] === 'modified' && $this->can_show_diff($row['path'], $baseline_files)) {
                    $url = add_query_arg(
                        array(
                            'page' => 'zss-file-integrity',
                            'report' => isset($active_report['report_id']) ? $active_report['report_id'] : '',
                            'diff' => '1',
                            'file' => $row['path'],
                        ),
                        admin_url('admin.php')
                    );
                    $url = wp_nonce_url($url, 'zss_diff');
                    $actions = '<a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('Diff Preview', 'zshield-sentinel') . '</a>';
                } elseif ($row['type'] === 'modified' && !empty($baseline) && empty($baseline_files)) {
                    $actions = '<span class="zss-muted">' . esc_html__('Recreate baseline to view diff', 'zshield-sentinel') . '</span>';
                }

                echo '<tr data-type="' . esc_attr($row['type']) . '" data-path="' . esc_attr(strtolower($row['path'])) . '">';
                echo '<td><span class="zss-badge zss-badge-' . esc_attr($row['type']) . '">' . esc_html(ucfirst($row['type'])) . '</span></td>';
                echo '<td>' . esc_html($this->format_scan_path($row['path'])) . '</td>';
                echo '<td>' . $actions . '</td>';
                echo '</tr>';
            }

            if (empty($rows)) {
                echo '<tr><td colspan="3">' . esc_html__('No changes detected.', 'zshield-sentinel') . '</td></tr>';
            }

            echo '</tbody></table>';
        }

        if (!empty($reports)) {
            echo '<div class="zss-card">';
            echo '<h3>' . esc_html__('Scan History', 'zshield-sentinel') . '</h3>';
            echo '<table class="widefat striped zss-table">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Time', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Added', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Modified', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Removed', 'zshield-sentinel') . '</th>';
            echo '<th>' . esc_html__('Report', 'zshield-sentinel') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($reports as $report) {
                $url = admin_url('admin.php?page=zss-scan-report&report=' . rawurlencode($report['report_id']));
                echo '<tr>';
                echo '<td>' . esc_html(isset($report['scanned_at']) ? $report['scanned_at'] : '-') . '</td>';
                echo '<td>' . esc_html(isset($report['added']) ? count($report['added']) : 0) . '</td>';
                echo '<td>' . esc_html(isset($report['modified']) ? count($report['modified']) : 0) . '</td>';
                echo '<td>' . esc_html(isset($report['removed']) ? count($report['removed']) : 0) . '</td>';
                echo '<td><a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('View Report', 'zshield-sentinel') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Actions', 'zshield-sentinel') . '</h3>';
        echo '<p>' . esc_html__('Create a baseline after a clean install. Run a scan any time you update plugins, themes, or custom code.', 'zshield-sentinel') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('zss_create_baseline');
        echo '<input type="hidden" name="action" value="zss_create_baseline" />';
        submit_button(__('Create Baseline', 'zshield-sentinel'), 'secondary');
        echo '</form>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('zss_run_scan');
        echo '<input type="hidden" name="action" value="zss_run_scan" />';
        submit_button(__('Run Scan', 'zshield-sentinel'), 'primary');
        echo '</form>';
        echo '</div>';

        $this->render_shell_end();
    }

    public function maybe_redirect_report() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'zss-file-integrity') {
            return;
        }

        $report_id = isset($_GET['report']) ? sanitize_text_field(wp_unslash($_GET['report'])) : '';
        if (!$report_id) {
            return;
        }

        $reports = get_option('zss_scan_reports', array());
        if (!is_array($reports)) {
            $reports = array();
        }

        $active_report = $this->find_report_by_id($reports, $report_id);
        if ($active_report) {
            wp_safe_redirect(admin_url('admin.php?page=zss-scan-report&report=' . rawurlencode($report_id)));
            exit;
        }
    }

    public function render_scan_report_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $reports = get_option('zss_scan_reports', array());
        if (!is_array($reports)) {
            $reports = array();
        }

        $baseline = get_option('zss_file_integrity_baseline', array());
        $baseline_files = isset($baseline['files']) && is_array($baseline['files']) ? $baseline['files'] : array();

        $report_id = isset($_GET['report']) ? sanitize_text_field(wp_unslash($_GET['report'])) : '';
        $active_report = $this->find_report_by_id($reports, $report_id);

        $actions = array(
            array(
                'label' => __('Back to File Integrity', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-file-integrity'),
            ),
        );
        $this->render_shell_start(
            __('Scan Report', 'zshield-sentinel'),
            __('Detailed view of a single file integrity scan.', 'zshield-sentinel'),
            'zss-file-integrity',
            $actions
        );

        if (!$active_report) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Report not found. Please run a new scan to generate fresh reports.', 'zshield-sentinel') . '</p></div>';
            $this->render_shell_end();
            return;
        }

        $added = isset($active_report['added']) ? $active_report['added'] : array();
        $modified = isset($active_report['modified']) ? $active_report['modified'] : array();
        $removed = isset($active_report['removed']) ? $active_report['removed'] : array();

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Report Info', 'zshield-sentinel') . '</h3>';
        echo '<p><strong>' . esc_html__('Report ID:', 'zshield-sentinel') . '</strong> ' . esc_html($active_report['report_id']) . '</p>';
        echo '<p><strong>' . esc_html__('Scanned at:', 'zshield-sentinel') . '</strong> ' . esc_html($active_report['scanned_at']) . '</p>';
        echo '</div>';

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('Summary', 'zshield-sentinel') . '</h3>';
        echo '<p>' . esc_html(sprintf('Added: %d | Modified: %d | Removed: %d', count($added), count($modified), count($removed))) . '</p>';
        echo '</div>';

        echo '<div class="zss-card zss-filter-bar">';
        echo '<label for="zss-filter-type">' . esc_html__('Filter', 'zshield-sentinel') . '</label> ';
        echo '<select id="zss-filter-type">';
        echo '<option value="all">' . esc_html__('All', 'zshield-sentinel') . '</option>';
        echo '<option value="added">' . esc_html__('Added', 'zshield-sentinel') . '</option>';
        echo '<option value="modified">' . esc_html__('Modified', 'zshield-sentinel') . '</option>';
        echo '<option value="removed">' . esc_html__('Removed', 'zshield-sentinel') . '</option>';
        echo '</select> ';
        echo '<label for="zss-filter-search">' . esc_html__('Search', 'zshield-sentinel') . '</label> ';
        echo '<input type="search" id="zss-filter-search" placeholder="' . esc_attr__('Search file path...', 'zshield-sentinel') . '" />';
        echo '<span class="zss-filter-count" id="zss-filter-count"></span>';
        echo '</div>';

        echo '<h2>' . esc_html__('Scan Results (Details)', 'zshield-sentinel') . '</h2>';
        echo '<table class="widefat striped zss-table" id="zss-scan-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Type', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('File Path', 'zshield-sentinel') . '</th>';
        echo '<th>' . esc_html__('Actions', 'zshield-sentinel') . '</th>';
        echo '</tr></thead><tbody>';

        $rows = $this->build_scan_rows($added, $modified, $removed);
        foreach ($rows as $row) {
            $actions = '-';
            $details_url = add_query_arg(
                array(
                    'page' => 'zss-file-details',
                    'report' => $active_report['report_id'],
                    'file' => $row['path'],
                ),
                admin_url('admin.php')
            );
            $details_url = wp_nonce_url($details_url, 'zss_file_details');
            $details_btn = '<a class="button button-small" href="' . esc_url($details_url) . '">' . esc_html__('View Details', 'zshield-sentinel') . '</a>';

            if ($row['type'] === 'modified' && $this->can_show_diff($row['path'], $baseline_files)) {
                $url = add_query_arg(
                    array(
                        'page' => 'zss-file-details',
                        'report' => $active_report['report_id'],
                        'file' => $row['path'],
                    ),
                    admin_url('admin.php')
                );
                $url = wp_nonce_url(add_query_arg('diff', '1', $url), 'zss_diff');
                $actions = $details_btn . ' <a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('View Diff', 'zshield-sentinel') . '</a>';
            } elseif ($row['type'] === 'modified' && !empty($baseline) && empty($baseline_files)) {
                $actions = $details_btn . ' <span class="zss-muted">' . esc_html__('Recreate baseline to view diff', 'zshield-sentinel') . '</span>';
            } else {
                $actions = $details_btn;
            }

            echo '<tr data-type="' . esc_attr($row['type']) . '" data-path="' . esc_attr(strtolower($row['path'])) . '">';
            echo '<td><span class="zss-badge zss-badge-' . esc_attr($row['type']) . '">' . esc_html(ucfirst($row['type'])) . '</span></td>';
            echo '<td>' . esc_html($this->format_scan_path($row['path'])) . '</td>';
            echo '<td>' . $actions . '</td>';
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="3">' . esc_html__('No changes detected.', 'zshield-sentinel') . '</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_shell_end();
    }

    public function render_file_details_page() {
        if (!current_user_can(Utils::required_capability())) {
            return;
        }

        $reports = get_option('zss_scan_reports', array());
        if (!is_array($reports)) {
            $reports = array();
        }

        $baseline = get_option('zss_file_integrity_baseline', array());
        $baseline_files = isset($baseline['files']) && is_array($baseline['files']) ? $baseline['files'] : array();

        $report_id = isset($_GET['report']) ? sanitize_text_field(wp_unslash($_GET['report'])) : '';
        $file = isset($_GET['file']) ? sanitize_text_field(wp_unslash($_GET['file'])) : '';
        $nonce_ok = isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'zss_file_details');

        $actions = array(
            array(
                'label' => __('Back to Scan Report', 'zshield-sentinel'),
                'url' => admin_url('admin.php?page=zss-scan-report&report=' . rawurlencode($report_id)),
            ),
        );
        $this->render_shell_start(
            __('File Details', 'zshield-sentinel'),
            __('Detailed analysis of a single file change.', 'zshield-sentinel'),
            'zss-file-integrity',
            $actions
        );

        if (!$nonce_ok || !$file) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Invalid request. Please try again.', 'zshield-sentinel') . '</p></div>';
            $this->render_shell_end();
            return;
        }

        $details = $this->build_file_details($file, $baseline_files);
        if (!empty($details['error'])) {
            echo '<div class="notice notice-warning"><p>' . esc_html($details['error']) . '</p></div>';
            $this->render_shell_end();
            return;
        }

        echo '<div class="zss-card">';
        echo '<h3>' . esc_html__('File Summary', 'zshield-sentinel') . '</h3>';
        echo '<p><strong>' . esc_html__('Path:', 'zshield-sentinel') . '</strong> ' . esc_html($details['path']) . '</p>';
        echo '<p><strong>' . esc_html__('Status:', 'zshield-sentinel') . '</strong> ' . esc_html($details['status']) . '</p>';
        echo '<p><strong>' . esc_html__('Current size:', 'zshield-sentinel') . '</strong> ' . esc_html($details['size']) . '</p>';
        echo '<p><strong>' . esc_html__('Current hash:', 'zshield-sentinel') . '</strong> ' . esc_html($details['current_hash']) . '</p>';
        echo '<p><strong>' . esc_html__('Baseline hash:', 'zshield-sentinel') . '</strong> ' . esc_html($details['baseline_hash']) . '</p>';
        echo '<p><strong>' . esc_html__('Last modified:', 'zshield-sentinel') . '</strong> ' . esc_html($details['modified_at']) . '</p>';
        echo '</div>';

        $stats = $this->build_change_stats($file, $baseline_files);
        if (!empty($stats['notice'])) {
            echo '<div class="notice notice-warning"><p>' . esc_html($stats['notice']) . '</p></div>';
        } else {
            echo '<div class="zss-card">';
            echo '<h3>' . esc_html__('Change Highlights', 'zshield-sentinel') . '</h3>';
            echo '<p>' . esc_html(sprintf('Added lines: %d | Removed lines: %d | Total lines: %d', $stats['added'], $stats['removed'], $stats['total'])) . '</p>';
            echo '</div>';
        }

        $show_diff = isset($_GET['diff']);
        if ($show_diff) {
            $nonce_ok = isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'zss_diff');
            if ($nonce_ok) {
                $diff_result = $this->build_diff_preview($file, $baseline_files);
                if (!empty($diff_result['error'])) {
                    $recreate = '';
                    if (!empty($diff_result['code']) && $diff_result['code'] === 'zss_diff_baseline_missing') {
                        $recreate = ' <a href="' . esc_url(admin_url('admin.php?page=zss-file-integrity')) . '">' . esc_html__('Recreate Baseline', 'zshield-sentinel') . '</a>';
                    }
                    echo '<div class="notice notice-warning"><p>' . esc_html($diff_result['error']) . $recreate . '</p></div>';
                } elseif (!empty($diff_result['html'])) {
                    echo '<div class="zss-card">';
                    echo '<h3>' . esc_html__('Diff Preview', 'zshield-sentinel') . '</h3>';
                    echo '<p class="description">' . esc_html($diff_result['title']) . '</p>';
                    echo $diff_result['html'];
                    echo '</div>';
                }
            }
        } else {
            $diff_url = add_query_arg(
                array(
                    'page' => 'zss-file-details',
                    'report' => $report_id,
                    'file' => $file,
                    'diff' => '1',
                ),
                admin_url('admin.php')
            );
            $diff_url = wp_nonce_url($diff_url, 'zss_diff');
            echo '<a class="button button-primary" href="' . esc_url($diff_url) . '">' . esc_html__('Show Diff Preview', 'zshield-sentinel') . '</a>';
        }

        $this->render_shell_end();
    }

    private function extract_report_id($message) {
        if (preg_match('/Report ID:\\s([a-f0-9]{10})/i', $message, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function format_event_label($event_type) {
        $map = array(
            'settings_update' => __('Settings Updated', 'zshield-sentinel'),
            'file_scan' => __('File Scan', 'zshield-sentinel'),
            'file_baseline' => __('Baseline Created', 'zshield-sentinel'),
            'login_failed' => __('Login Failed', 'zshield-sentinel'),
            'login_success' => __('Login Success', 'zshield-sentinel'),
            'login_lockout' => __('Login Lockout', 'zshield-sentinel'),
            'login_blocked' => __('Login Blocked', 'zshield-sentinel'),
            'file_scan_skipped' => __('Scan Skipped', 'zshield-sentinel'),
            'malware_scan' => __('Malware Scan', 'zshield-sentinel'),
            'malware_scan_alert' => __('Malware Alert', 'zshield-sentinel'),
            'malware_quarantine' => __('Quarantine', 'zshield-sentinel'),
            'malware_restore' => __('Quarantine Restore', 'zshield-sentinel'),
            'firewall_block' => __('Firewall Block', 'zshield-sentinel'),
        );
        if (isset($map[$event_type])) {
            return $map[$event_type];
        }
        return ucwords(str_replace('_', ' ', $event_type));
    }

    private function severity_label($severity) {
        if ($severity >= 4) {
            return 'high';
        }
        if ($severity >= 3) {
            return 'medium';
        }
        return 'low';
    }

    private function severity_class($severity) {
        if ($severity >= 4) {
            return 'zss-risk-high';
        }
        if ($severity >= 3) {
            return 'zss-risk-medium';
        }
        return 'zss-risk-low';
    }

    private function malware_suggestion($finding) {
        $rule_key = isset($finding['rule_key']) ? $finding['rule_key'] : '';
        if (!$rule_key && !empty($finding['rule'])) {
            $rule_key = $this->rule_key_from_label($finding['rule']);
        }

        $path = isset($finding['file']) ? $finding['file'] : '';
        if ($path && strpos(str_replace('\\', '/', $path), 'zshield-sentinel/includes/class-zss-malware-scan.php') !== false) {
            return __('This match is from the scanner rule labels. It is not an execution point. No change required.', 'zshield-sentinel');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'js') {
            return __('If this is a minified vendor file, verify the original source and replace with a clean copy. JavaScript may trigger PHP-focused rules and can be a false positive.', 'zshield-sentinel');
        }

        switch ($rule_key) {
            case 'eval':
                return __('Remove eval when possible. If you are parsing data, replace with safe parsing like <code>json_decode()</code> or explicit whitelisted logic.', 'zshield-sentinel');
            case 'base64_decode':
                return __('Base64 is often used to hide payloads. Only keep it for trusted data and validate with <code>base64_decode($data, true)</code> plus strict checks.', 'zshield-sentinel');
            case 'gzinflate':
                return __('Compressed payloads can hide malicious code. Avoid <code>gzinflate()</code> for dynamic data or replace with known safe assets.', 'zshield-sentinel');
            case 'rot13':
                return __('Obfuscation can hide intent. Replace ROT13 strings with plain text or remove if unnecessary.', 'zshield-sentinel');
            case 'preg_replace_e':
                return __('The /e modifier executes code. Replace with <code>preg_replace_callback()</code> and avoid dynamic execution.', 'zshield-sentinel');
            case 'system_exec':
                return __('Avoid OS command execution. If needed, whitelist commands, sanitize args, or use WordPress APIs instead of system calls.', 'zshield-sentinel');
            case 'php_write':
                return __('Writing PHP files is risky. Store uploads outside executable paths and never write user input directly to PHP files.', 'zshield-sentinel');
            case 'remote_request':
                return __('Prefer WordPress HTTP API like <code>wp_remote_get()</code> with strict URL validation and timeouts.', 'zshield-sentinel');
            case 'assert':
                return __('Remove <code>assert()</code> and use explicit checks with error handling. Assertions can be abused.', 'zshield-sentinel');
            case 'concat':
                return __('Review dynamic concatenation carefully. Ensure no untrusted input reaches file paths or executable code.', 'zshield-sentinel');
            default:
                return __('Review this line carefully. If it is expected, confirm the file source and checksum. Otherwise remove or replace.', 'zshield-sentinel');
        }
    }

    private function rule_key_from_label($label) {
        $label = strtolower($label);
        if (strpos($label, 'eval') !== false) {
            return 'eval';
        }
        if (strpos($label, 'base64') !== false) {
            return 'base64_decode';
        }
        if (strpos($label, 'gzinflate') !== false) {
            return 'gzinflate';
        }
        if (strpos($label, 'rot13') !== false) {
            return 'rot13';
        }
        if (strpos($label, 'preg replace') !== false) {
            return 'preg_replace_e';
        }
        if (strpos($label, 'system execution') !== false) {
            return 'system_exec';
        }
        if (strpos($label, 'file write') !== false) {
            return 'php_write';
        }
        if (strpos($label, 'remote request') !== false) {
            return 'remote_request';
        }
        if (strpos($label, 'assert') !== false) {
            return 'assert';
        }
        if (strpos($label, 'concatenation') !== false) {
            return 'concat';
        }
        return '';
    }

    private function find_report_by_id($reports, $report_id) {
        if (!$report_id) {
            return null;
        }
        foreach ($reports as $report) {
            if (isset($report['report_id']) && $report['report_id'] === $report_id) {
                return $report;
            }
        }
        return null;
    }

    private function build_scan_rows($added, $modified, $removed) {
        $rows = array();
        foreach ($added as $path) {
            $rows[] = array('type' => 'added', 'path' => $path);
        }
        foreach ($modified as $path) {
            $rows[] = array('type' => 'modified', 'path' => $path);
        }
        foreach ($removed as $path) {
            $rows[] = array('type' => 'removed', 'path' => $path);
        }
        return $rows;
    }

    private function can_show_diff($path, $baseline_files) {
        if (!isset($baseline_files[$path])) {
            return false;
        }
        if (empty($baseline_files[$path]['content'])) {
            return false;
        }
        return file_exists($path);
    }

    private function build_diff_preview($path, $baseline_files) {
        $normalized = str_replace('\\', '/', $path);
        $content_dir = str_replace('\\', '/', WP_CONTENT_DIR);
        if (strpos($normalized, $content_dir) !== 0) {
            return array('error' => __('Invalid file path.', 'zshield-sentinel'));
        }

        if (!isset($baseline_files[$path]) || empty($baseline_files[$path]['content'])) {
            return array(
                'error' => __('Diff preview is not available for this file. Please recreate the baseline to enable diffs.', 'zshield-sentinel'),
                'code' => 'zss_diff_baseline_missing',
            );
        }

        if (!file_exists($path)) {
            return array('error' => __('File not found on disk.', 'zshield-sentinel'));
        }

        $baseline_content = $baseline_files[$path]['content'];
        $current_content = file_get_contents($path);
        if ($current_content === false) {
            return array('error' => __('Unable to read current file.', 'zshield-sentinel'));
        }

        require_once ABSPATH . 'wp-admin/includes/misc.php';
        $diff = wp_text_diff(
            $baseline_content,
            $current_content,
            array(
                'title_left' => __('Baseline', 'zshield-sentinel'),
                'title_right' => __('Current', 'zshield-sentinel'),
            )
        );

        if (!$diff) {
            $diff = '<p>' . esc_html__('No differences found or diff could not be generated.', 'zshield-sentinel') . '</p>';
        }

        return array(
            'title' => $this->format_scan_path($path),
            'html' => $diff,
        );
    }

    private function build_file_details($path, $baseline_files) {
        $normalized = str_replace('\\', '/', $path);
        $content_dir = str_replace('\\', '/', WP_CONTENT_DIR);
        if (strpos($normalized, $content_dir) !== 0) {
            return array('error' => __('Invalid file path.', 'zshield-sentinel'));
        }

        $exists = file_exists($path);
        $size = $exists ? size_format(filesize($path)) : __('Not available', 'zshield-sentinel');
        $current_hash = $exists ? hash_file('sha256', $path) : __('Not available', 'zshield-sentinel');
        $modified_at = $exists ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($path)) : __('Not available', 'zshield-sentinel');

        $baseline_hash = isset($baseline_files[$path]['hash']) ? $baseline_files[$path]['hash'] : __('Not available', 'zshield-sentinel');
        $status = $exists ? __('Present', 'zshield-sentinel') : __('Missing', 'zshield-sentinel');

        return array(
            'path' => $this->format_scan_path($path),
            'status' => $status,
            'size' => $size,
            'current_hash' => $current_hash,
            'baseline_hash' => $baseline_hash,
            'modified_at' => $modified_at,
        );
    }

    private function build_change_stats($path, $baseline_files) {
        if (!isset($baseline_files[$path]) || empty($baseline_files[$path]['content'])) {
            return array('notice' => __('Change highlights require a new baseline. Please recreate the baseline.', 'zshield-sentinel'));
        }
        if (!file_exists($path)) {
            return array('notice' => __('File not found on disk.', 'zshield-sentinel'));
        }

        $baseline_content = $baseline_files[$path]['content'];
        $current_content = file_get_contents($path);
        if ($current_content === false) {
            return array('notice' => __('Unable to read current file.', 'zshield-sentinel'));
        }

        $base_lines = preg_split("/\\r\\n|\\r|\\n/", $baseline_content);
        $cur_lines = preg_split("/\\r\\n|\\r|\\n/", $current_content);

        $base_counts = array();
        foreach ($base_lines as $line) {
            $base_counts[$line] = isset($base_counts[$line]) ? $base_counts[$line] + 1 : 1;
        }
        $cur_counts = array();
        foreach ($cur_lines as $line) {
            $cur_counts[$line] = isset($cur_counts[$line]) ? $cur_counts[$line] + 1 : 1;
        }

        $added = 0;
        foreach ($cur_counts as $line => $count) {
            $base_count = isset($base_counts[$line]) ? $base_counts[$line] : 0;
            if ($count > $base_count) {
                $added += ($count - $base_count);
            }
        }

        $removed = 0;
        foreach ($base_counts as $line => $count) {
            $cur_count = isset($cur_counts[$line]) ? $cur_counts[$line] : 0;
            if ($count > $cur_count) {
                $removed += ($count - $cur_count);
            }
        }

        $total = count($cur_lines);

        return array(
            'added' => $added,
            'removed' => $removed,
            'total' => $total,
        );
    }
}

