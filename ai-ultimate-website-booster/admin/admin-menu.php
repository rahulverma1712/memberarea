<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'aiwb_register_admin_menu' );
add_action( 'admin_init', 'aiwb_register_settings' );

function aiwb_register_admin_menu() {
    add_menu_page(
        esc_html__( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ),
        esc_html__( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-dashboard',
        'aiwb_render_dashboard_page',
        'dashicons-superhero',
        26
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Dashboard', 'ai-ultimate-website-booster' ),
        esc_html__( 'Dashboard', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-dashboard',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Settings', 'ai-ultimate-website-booster' ),
        esc_html__( 'Settings', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-settings',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'AI Content', 'ai-ultimate-website-booster' ),
        esc_html__( 'AI Content', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-ai-content',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'AI Blog Ideas', 'ai-ultimate-website-booster' ),
        esc_html__( 'AI Blog Ideas', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-ai-blog-ideas',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Bulk Post Generator', 'ai-ultimate-website-booster' ),
        esc_html__( 'Bulk Post Generator', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-bulk-post-generator',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'SEO Tools', 'ai-ultimate-website-booster' ),
        esc_html__( 'SEO Tools', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-seo-tools',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Popups', 'ai-ultimate-website-booster' ),
        esc_html__( 'Popups', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-popups',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Health Scanner', 'ai-ultimate-website-booster' ),
        esc_html__( 'Health Scanner', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-health-scanner',
        'aiwb_render_dashboard_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Security Overview', 'ai-ultimate-website-booster' ),
        esc_html__( 'Security Overview', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security',
        'aiwb_render_security_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Login Security', 'ai-ultimate-website-booster' ),
        esc_html__( 'Login Security', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-login',
        'aiwb_render_security_login_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Firewall', 'ai-ultimate-website-booster' ),
        esc_html__( 'Firewall', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-firewall',
        'aiwb_render_security_firewall_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'File Integrity', 'ai-ultimate-website-booster' ),
        esc_html__( 'File Integrity', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-integrity',
        'aiwb_render_security_integrity_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Malware Scanner', 'ai-ultimate-website-booster' ),
        esc_html__( 'Malware Scanner', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-malware',
        'aiwb_render_security_malware_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Hardening', 'ai-ultimate-website-booster' ),
        esc_html__( 'Hardening', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-hardening',
        'aiwb_render_security_hardening_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Security Headers', 'ai-ultimate-website-booster' ),
        esc_html__( 'Security Headers', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-headers',
        'aiwb_render_security_headers_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Security Logs', 'ai-ultimate-website-booster' ),
        esc_html__( 'Security Logs', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-security-logs',
        'aiwb_render_security_logs_page'
    );

    add_submenu_page(
        'aiwb-dashboard',
        esc_html__( 'Documentation', 'ai-ultimate-website-booster' ),
        esc_html__( 'Documentation', 'ai-ultimate-website-booster' ),
        'manage_options',
        'aiwb-documentation',
        'aiwb_render_documentation_page'
    );
}

function aiwb_register_settings() {
    register_setting( 'aiwb_settings_group', 'aiwb_settings', 'aiwb_sanitize_settings' );
}

function aiwb_sanitize_settings( $input ) {
    $existing = get_option( 'aiwb_settings', array() );
    $output = is_array( $existing ) ? $existing : array();
    if ( isset( $input['popup_trigger'] ) ) {
        $output['popup_trigger'] = in_array( $input['popup_trigger'], array( 'exit_intent', 'scroll', 'time_delay' ), true ) ? $input['popup_trigger'] : 'exit_intent';
    }
    if ( isset( $input['popup_delay'] ) ) {
        $output['popup_delay'] = absint( $input['popup_delay'] );
    }
    if ( isset( $input['popup_devices'] ) ) {
        $output['popup_devices'] = sanitize_text_field( $input['popup_devices'] );
    }
    if ( isset( $input['popup_scroll_percent'] ) ) {
        $output['popup_scroll_percent'] = absint( $input['popup_scroll_percent'] );
    }
    $output['api_provider'] = in_array( $input['api_provider'] ?? 'openai', array( 'openai' ), true ) ? $input['api_provider'] : 'openai';
    $output['api_key'] = sanitize_text_field( $input['api_key'] ?? '' );
    $output['api_model'] = sanitize_text_field( $input['api_model'] ?? 'gpt-4o-mini' );
    $output['api_endpoint'] = esc_url_raw( $input['api_endpoint'] ?? 'https://api.openai.com/v1/chat/completions' );
    $output['image_provider'] = in_array( $input['image_provider'] ?? '', array( 'pexels', 'pixabay' ), true ) ? $input['image_provider'] : '';
    $output['pexels_api_key'] = sanitize_text_field( $input['pexels_api_key'] ?? '' );
    $output['pixabay_api_key'] = sanitize_text_field( $input['pixabay_api_key'] ?? '' );
    if ( isset( $input['popup_template'] ) ) {
        $output['popup_template'] = in_array( $input['popup_template'], array( 'template_1', 'template_2', 'template_3' ), true ) ? $input['popup_template'] : 'template_1';
    }
    if ( isset( $input['popup_headline'] ) ) {
        $output['popup_headline'] = sanitize_text_field( $input['popup_headline'] );
    }
    if ( isset( $input['popup_message'] ) ) {
        $output['popup_message'] = sanitize_textarea_field( $input['popup_message'] );
    }
    if ( isset( $input['popup_button_text'] ) ) {
        $output['popup_button_text'] = sanitize_text_field( $input['popup_button_text'] );
    }
    if ( isset( $input['popup_button_url'] ) ) {
        $output['popup_button_url'] = esc_url_raw( $input['popup_button_url'] );
    }
    if ( isset( $input['popup_enabled'] ) ) {
        $output['popup_enabled'] = ! empty( $input['popup_enabled'] ) ? '1' : '0';
    }
    $output['schema_type'] = in_array( $input['schema_type'] ?? 'article', array( 'article', 'faq', 'product' ), true ) ? $input['schema_type'] : 'article';
    $output['schema_faq'] = sanitize_textarea_field( $input['schema_faq'] ?? '' );
    $output['schema_product'] = wp_kses_post( $input['schema_product'] ?? '' );
    $output['automation_enabled'] = ! empty( $input['automation_enabled'] ) ? '1' : '0';
    $output['automation_frequency'] = in_array( $input['automation_frequency'] ?? 'daily', array( 'daily', 'weekly' ), true ) ? $input['automation_frequency'] : 'daily';
    $output['automation_post_age_days'] = absint( $input['automation_post_age_days'] ?? 180 );
    $output['automation_post_limit'] = absint( $input['automation_post_limit'] ?? 3 );
    $output['automation_alt_batch'] = absint( $input['automation_alt_batch'] ?? 10 );
    return $output;
}

function aiwb_render_dashboard_page() {
    include AIWB_PATH . 'admin/dashboard.php';
}

function aiwb_render_security_page() {
    include AIWB_PATH . 'admin/security-center.php';
}

function aiwb_render_security_login_page() {
    $aiwb_security_module = array(
        'key' => 'login',
        'title' => __( 'Login Security', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Monitor login activity and verify brute-force protections.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'Failed Logins (7d)', 'ai-ultimate-website-booster' ), 'value' => AIWB_Health::count_action( 'login_failed', 7 ), 'class' => 'is-danger' ),
            array( 'label' => __( 'Successful Logins (7d)', 'ai-ultimate-website-booster' ), 'value' => AIWB_Health::count_action( 'login_success', 7 ), 'class' => 'is-good' ),
            array( 'label' => __( 'Brute-force Protection', 'ai-ultimate-website-booster' ), 'value' => __( 'Status', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'Brute-force Protection', 'ai-ultimate-website-booster' ) ),
        ),
        'checks' => array(
            __( 'Brute-force Protection', 'ai-ultimate-website-booster' ),
            __( 'Default Admin Username', 'ai-ultimate-website-booster' ),
            __( 'Admin Email', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Enable a login throttling plugin to block repeated attempts.', 'ai-ultimate-website-booster' ),
            __( 'Use strong passwords and enforce 2FA for admin roles.', 'ai-ultimate-website-booster' ),
        ),
        'recent_filter' => array( 'login_failed', 'login_success', 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_firewall_page() {
    $aiwb_security_module = array(
        'key' => 'firewall',
        'title' => __( 'Firewall', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Verify WAF coverage and review blocked traffic.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'Firewall Blocks (7d)', 'ai-ultimate-website-booster' ), 'value' => AIWB_Health::count_action( 'firewall_block', 7 ), 'class' => 'is-warn' ),
            array( 'label' => __( 'IPs Blocked (7d)', 'ai-ultimate-website-booster' ), 'value' => AIWB_Health::count_action( 'firewall_block', 7 ), 'class' => 'is-info' ),
            array( 'label' => __( 'WAF Detected', 'ai-ultimate-website-booster' ), 'value' => __( 'Status', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'WAF / Firewall Detected', 'ai-ultimate-website-booster' ) ),
        ),
        'checks' => array(
            __( 'WAF / Firewall Detected', 'ai-ultimate-website-booster' ),
            __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Enable a firewall plugin or WAF from your hosting provider.', 'ai-ultimate-website-booster' ),
            __( 'Review blocked IPs and add allowlist rules when needed.', 'ai-ultimate-website-booster' ),
        ),
        'recent_filter' => array( 'firewall_block', 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_integrity_page() {
    $aiwb_security_module = array(
        'key' => 'integrity',
        'title' => __( 'File Integrity', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Track core file integrity and file permission health.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'File Anomalies', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'Core File Integrity', 'ai-ultimate-website-booster' ) ),
            array( 'label' => __( 'Permissions Scan', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-warn', 'check_label' => __( 'File Permissions (wp-content/uploads/plugins)', 'ai-ultimate-website-booster' ) ),
        ),
        'checks' => array(
            __( 'Core File Integrity', 'ai-ultimate-website-booster' ),
            __( 'File Permissions (wp-content/uploads/plugins)', 'ai-ultimate-website-booster' ),
            __( 'wp-config.php Permissions', 'ai-ultimate-website-booster' ),
            __( '.htaccess Permissions', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Keep core files untouched and update WordPress regularly.', 'ai-ultimate-website-booster' ),
            __( 'Ensure wp-config.php and .htaccess are locked down.', 'ai-ultimate-website-booster' ),
        ),
        'recent_filter' => array( 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_malware_page() {
    $aiwb_security_module = array(
        'key' => 'malware',
        'title' => __( 'Malware Scanner', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Run heuristic scans across plugins and themes.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'Files Scanned', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'data_key' => array( 'malware_scan', 'files_scanned' ) ),
            array( 'label' => __( 'Findings', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-danger', 'data_key' => array( 'malware_scan', 'findings' ) ),
        ),
        'checks' => array(
            __( 'Core File Integrity', 'ai-ultimate-website-booster' ),
            __( 'Plugin Updates', 'ai-ultimate-website-booster' ),
            __( 'Theme Updates', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Quarantine suspicious files and reinstall clean copies.', 'ai-ultimate-website-booster' ),
            __( 'Keep plugins/themes updated to close vulnerabilities.', 'ai-ultimate-website-booster' ),
        ),
        'show_samples' => true,
        'recent_filter' => array( 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_hardening_page() {
    $aiwb_security_module = array(
        'key' => 'hardening',
        'title' => __( 'Hardening', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Review core hardening settings and risk indicators.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'XML-RPC Status', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'XML-RPC Disabled', 'ai-ultimate-website-booster' ) ),
            array( 'label' => __( 'File Editor', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'File Editor Disabled', 'ai-ultimate-website-booster' ) ),
            array( 'label' => __( 'Auto Updates', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'Auto Updates', 'ai-ultimate-website-booster' ) ),
        ),
        'checks' => array(
            __( 'XML-RPC Disabled', 'ai-ultimate-website-booster' ),
            __( 'File Editor Disabled', 'ai-ultimate-website-booster' ),
            __( 'Debug Mode', 'ai-ultimate-website-booster' ),
            __( 'Auto Updates', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Disable XML-RPC if not required by integrations.', 'ai-ultimate-website-booster' ),
            __( 'Turn off WP_DEBUG on production sites.', 'ai-ultimate-website-booster' ),
        ),
        'recent_filter' => array( 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_headers_page() {
    $aiwb_security_module = array(
        'key' => 'headers',
        'title' => __( 'Security Headers', 'ai-ultimate-website-booster' ),
        'subtitle' => __( 'Validate HTTP security headers and transport policies.', 'ai-ultimate-website-booster' ),
        'kpis' => array(
            array( 'label' => __( 'Header Coverage', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-info', 'check_label' => __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ) ),
            array( 'label' => __( 'SSL Enabled', 'ai-ultimate-website-booster' ), 'value' => __( 'Check Report', 'ai-ultimate-website-booster' ), 'class' => 'is-good', 'check_label' => __( 'SSL Enabled', 'ai-ultimate-website-booster' ) ),
        ),
        'checks' => array(
            __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ),
            __( 'SSL Enabled', 'ai-ultimate-website-booster' ),
        ),
        'tips' => array(
            __( 'Enable HSTS once SSL is fully deployed.', 'ai-ultimate-website-booster' ),
            __( 'Add CSP policies to reduce script injection risks.', 'ai-ultimate-website-booster' ),
        ),
        'recent_filter' => array( 'full_security' ),
    );
    include AIWB_PATH . 'admin/security-module.php';
}

function aiwb_render_security_logs_page() {
    include AIWB_PATH . 'admin/security-logs.php';
}

function aiwb_render_documentation_page() {
    include AIWB_PATH . 'admin/documentation.php';
}
