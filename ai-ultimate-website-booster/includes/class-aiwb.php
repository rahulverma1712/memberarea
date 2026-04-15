<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Main {

    public static function init() {
        self::load_dependencies();
        self::register_hooks();
    }

    public static function load_dependencies() {
        require_once AIWB_PATH . 'admin/admin-menu.php';
        require_once AIWB_PATH . 'includes/class-aiwb-ai.php';
        require_once AIWB_PATH . 'includes/class-aiwb-ajax.php';
        require_once AIWB_PATH . 'includes/class-aiwb-schema.php';
        require_once AIWB_PATH . 'includes/class-aiwb-health.php';
        require_once AIWB_PATH . 'includes/class-aiwb-popup.php';
        require_once AIWB_PATH . 'includes/class-aiwb-content-updater.php';
        require_once AIWB_PATH . 'includes/class-aiwb-rest.php';
    }

    public static function register_hooks() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
        add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
        add_action( 'admin_init', array( __CLASS__, 'set_default_options' ) );
        add_action( 'update_option_aiwb_settings', array( __CLASS__, 'maybe_schedule_automation' ), 10, 2 );
        add_action( 'aiwb_daily_automation', array( __CLASS__, 'run_automation' ) );
        add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_seo_meta_box' ) );
        add_action( 'save_post', array( __CLASS__, 'save_seo_meta_box' ) );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'filter_document_title' ), 20 );
        add_action( 'wp_head', array( __CLASS__, 'output_seo_meta' ), 5 );
        add_action( 'wp_login_failed', array( __CLASS__, 'log_login_failed' ) );
        add_action( 'wp_login', array( __CLASS__, 'log_login_success' ), 10, 2 );
        add_action( 'init', array( __CLASS__, 'enforce_firewall' ) );
        add_action( 'update_option_aiwb_security_schedule', array( __CLASS__, 'maybe_schedule_security_scan' ), 10, 2 );
        add_action( 'aiwb_security_scheduled_full', array( __CLASS__, 'run_scheduled_security_scan' ) );
    }

    public static function add_seo_meta_box() {
        add_meta_box(
            'aiwb_seo_meta_box',
            __( 'AIWB SEO Meta', 'ai-ultimate-website-booster' ),
            array( __CLASS__, 'render_seo_meta_box' ),
            'post',
            'side',
            'default'
        );
    }

    public static function render_seo_meta_box( $post ) {
        $meta_title = get_post_meta( $post->ID, '_aiwb_meta_title', true );
        $meta_desc = get_post_meta( $post->ID, '_aiwb_meta_description', true );
        $focus_keyword = get_post_meta( $post->ID, '_aiwb_focus_keyword', true );
        $faq_schema = get_post_meta( $post->ID, '_aiwb_faq_schema', true );
        wp_nonce_field( 'aiwb_seo_meta_box', 'aiwb_seo_meta_box_nonce' );
        ?>
        <p>
            <label for="aiwb_meta_title"><strong><?php esc_html_e( 'Meta Title', 'ai-ultimate-website-booster' ); ?></strong></label>
            <input type="text" id="aiwb_meta_title" name="aiwb_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" style="width:100%;">
        </p>
        <p>
            <label for="aiwb_focus_keyword"><strong><?php esc_html_e( 'Focus Keyword', 'ai-ultimate-website-booster' ); ?></strong></label>
            <input type="text" id="aiwb_focus_keyword" name="aiwb_focus_keyword" value="<?php echo esc_attr( $focus_keyword ); ?>" style="width:100%;">
        </p>
        <p>
            <label for="aiwb_meta_desc"><strong><?php esc_html_e( 'Meta Description', 'ai-ultimate-website-booster' ); ?></strong></label>
            <textarea id="aiwb_meta_desc" name="aiwb_meta_desc" rows="4" style="width:100%;"><?php echo esc_textarea( $meta_desc ); ?></textarea>
        </p>
        <p>
            <label for="aiwb_faq_schema"><strong><?php esc_html_e( 'FAQ Schema (JSON-LD)', 'ai-ultimate-website-booster' ); ?></strong></label>
            <textarea id="aiwb_faq_schema" name="aiwb_faq_schema" rows="4" style="width:100%;"><?php echo esc_textarea( $faq_schema ); ?></textarea>
        </p>
        <?php
    }

    public static function save_seo_meta_box( $post_id ) {
        if ( ! isset( $_POST['aiwb_seo_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiwb_seo_meta_box_nonce'] ) ), 'aiwb_seo_meta_box' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( isset( $_POST['aiwb_meta_title'] ) ) {
            update_post_meta( $post_id, '_aiwb_meta_title', sanitize_text_field( wp_unslash( $_POST['aiwb_meta_title'] ) ) );
        }
        if ( isset( $_POST['aiwb_meta_desc'] ) ) {
            update_post_meta( $post_id, '_aiwb_meta_description', sanitize_textarea_field( wp_unslash( $_POST['aiwb_meta_desc'] ) ) );
        }
        if ( isset( $_POST['aiwb_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_aiwb_focus_keyword', sanitize_text_field( wp_unslash( $_POST['aiwb_focus_keyword'] ) ) );
        }
        if ( isset( $_POST['aiwb_faq_schema'] ) ) {
            update_post_meta( $post_id, '_aiwb_faq_schema', wp_kses_post( wp_unslash( $_POST['aiwb_faq_schema'] ) ) );
        }
    }

    public static function filter_document_title( $title ) {
        if ( is_admin() || is_feed() || ! is_singular( 'post' ) ) {
            return $title;
        }
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return $title;
        }
        $meta_title = get_post_meta( $post_id, '_aiwb_meta_title', true );
        if ( $meta_title ) {
            return $meta_title;
        }
        return $title;
    }

    public static function output_seo_meta() {
        if ( is_admin() || is_feed() || ! is_singular( 'post' ) ) {
            return;
        }
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }
        $meta_desc = get_post_meta( $post_id, '_aiwb_meta_description', true );
        if ( $meta_desc ) {
            echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
        }
        $faq_schema = get_post_meta( $post_id, '_aiwb_faq_schema', true );
        if ( $faq_schema ) {
            $raw = trim( $faq_schema );
            if ( $raw !== '' ) {
                $decoded = json_decode( $raw, true );
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                    echo '<script type="application/ld+json">' . wp_json_encode( $decoded ) . '</script>' . "\n";
                } elseif ( $raw[0] === '{' || $raw[0] === '[' ) {
                    echo '<script type="application/ld+json">' . wp_kses( $raw, array() ) . '</script>' . "\n";
                }
            }
        }
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'aiwb' ) === false ) {
            return;
        }

        wp_enqueue_media();
        $admin_css_ver = defined( 'AIWB_VERSION' ) ? AIWB_VERSION : '1.0.0';
        $admin_css_path = AIWB_PATH . 'assets/css/admin.css';
        if ( file_exists( $admin_css_path ) ) {
            $admin_css_ver = $admin_css_ver . '.' . filemtime( $admin_css_path );
        }
        wp_enqueue_style( 'aiwb-admin-css', AIWB_URL . 'assets/css/admin.css', array(), $admin_css_ver );
        $admin_js_ver = defined( 'AIWB_VERSION' ) ? AIWB_VERSION : '1.0.0';
        $admin_js_path = AIWB_PATH . 'assets/js/admin.js';
        if ( file_exists( $admin_js_path ) ) {
            $admin_js_ver = $admin_js_ver . '.' . filemtime( $admin_js_path );
        }
        wp_enqueue_script( 'aiwb-admin-js', AIWB_URL . 'assets/js/admin.js', array( 'jquery' ), $admin_js_ver, true );
        wp_localize_script( 'aiwb-admin-js', 'aiwbData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'adminUrl' => admin_url( 'admin.php' ),
            'nonce'   => wp_create_nonce( 'aiwb_admin' ),
            'restUrl' => esc_url_raw( rest_url( 'aiwb/v1' ) ),
            'wpRestUrl' => esc_url_raw( rest_url( 'wp/v2' ) ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'siteUrl' => esc_url_raw( home_url() ),
            'lastSecurityReport' => get_option( 'aiwb_last_security_report', array() ),
            'lastModuleReports' => get_option( 'aiwb_last_module_report', array() ),
        ) );
    }

    public static function enqueue_frontend_assets() {
        wp_enqueue_style( 'aiwb-popup-css', AIWB_URL . 'assets/css/popup.css', array(), AIWB_VERSION );
        wp_enqueue_script( 'aiwb-popup-frontend', AIWB_URL . 'assets/js/popup.js', array( 'jquery' ), AIWB_VERSION, true );
    }

    public static function load_textdomain() {
        load_plugin_textdomain( 'ai-ultimate-website-booster', false, basename( dirname( __FILE__ ) ) . '/../languages' );
    }

    public static function set_default_options() {
        $defaults = array(
            'popup_trigger'     => 'exit_intent',
            'popup_delay'       => 5,
            'popup_devices'     => 'all',
            'popup_scroll_percent' => 35,
            'api_provider'      => 'openai',
            'api_key'           => '',
            'api_model'         => 'gpt-4o-mini',
            'api_endpoint'      => 'https://api.openai.com/v1/chat/completions',
            'image_provider'    => '',
            'pexels_api_key'    => '',
            'pixabay_api_key'   => '',
            'popup_template'    => 'template_1',
            'popup_headline'    => '',
            'popup_message'     => '',
            'popup_button_text' => '',
            'popup_button_url'  => '',
            'popup_enabled'     => '1',
            'schema_type'       => 'article',
            'schema_faq'        => '',
            'schema_product'    => '',
            'automation_enabled' => '0',
            'automation_frequency' => 'daily',
            'automation_post_age_days' => 180,
            'automation_post_limit' => 3,
            'automation_alt_batch' => 10,
        );

        if ( false === get_option( 'aiwb_settings' ) ) {
            add_option( 'aiwb_settings', $defaults );
        }
    }

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'aiwb_action_logs';
        $versions_table = $wpdb->prefix . 'aiwb_content_versions';
        $health_table = $wpdb->prefix . 'aiwb_health_scans';
        $popup_table = $wpdb->prefix . 'aiwb_popups';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action_name varchar(120) NOT NULL,
            action_data text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        $versions_sql = "CREATE TABLE {$versions_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            version_label varchar(60) NOT NULL,
            content longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) {$charset_collate};";

        $health_sql = "CREATE TABLE {$health_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scan_type varchar(80) NOT NULL,
            results longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        $popup_sql = "CREATE TABLE {$popup_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(120) NOT NULL,
            popup_type varchar(80) NOT NULL,
            settings_json longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        dbDelta( $versions_sql );
        dbDelta( $health_sql );
        dbDelta( $popup_sql );

        if ( ! wp_next_scheduled( 'aiwb_daily_automation' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'aiwb_daily_automation' );
        }
    }

    public static function add_cron_schedules( $schedules ) {
        if ( ! isset( $schedules['weekly'] ) ) {
            $schedules['weekly'] = array(
                'interval' => 7 * DAY_IN_SECONDS,
                'display'  => __( 'Once Weekly', 'ai-ultimate-website-booster' ),
            );
        }
        return $schedules;
    }

    public static function maybe_schedule_automation( $old_value, $value ) {
        $enabled = isset( $value['automation_enabled'] ) && '1' === $value['automation_enabled'];
        $timestamp = wp_next_scheduled( 'aiwb_daily_automation' );

        if ( ! $enabled && $timestamp ) {
            wp_unschedule_event( $timestamp, 'aiwb_daily_automation' );
            return;
        }

        $frequency = isset( $value['automation_frequency'] ) ? $value['automation_frequency'] : 'daily';
        if ( ! in_array( $frequency, array( 'daily', 'weekly' ), true ) ) {
            $frequency = 'daily';
        }

        if ( $enabled && ! $timestamp ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, $frequency, 'aiwb_daily_automation' );
        } elseif ( $enabled && $timestamp ) {
            wp_unschedule_event( $timestamp, 'aiwb_daily_automation' );
            wp_schedule_event( time() + HOUR_IN_SECONDS, $frequency, 'aiwb_daily_automation' );
        }
    }

    public static function maybe_schedule_security_scan( $old_value, $value ) {
        $enabled = isset( $value['enabled'] ) && '1' === $value['enabled'];
        $frequency = $value['frequency'] ?? 'weekly';
        if ( ! in_array( $frequency, array( 'daily', 'weekly' ), true ) ) {
            $frequency = 'weekly';
        }
        $hour = isset( $value['hour'] ) ? absint( $value['hour'] ) : 2;
        if ( $hour < 0 || $hour > 23 ) {
            $hour = 2;
        }

        $timestamp = wp_next_scheduled( 'aiwb_security_scheduled_full' );
        if ( ! $enabled ) {
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'aiwb_security_scheduled_full' );
            }
            return;
        }

        $next = strtotime( 'today ' . $hour . ':00' );
        if ( $next <= time() ) {
            $next = strtotime( '+1 day ' . $hour . ':00' );
        }

        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'aiwb_security_scheduled_full' );
        }
        wp_schedule_event( $next, $frequency, 'aiwb_security_scheduled_full' );
    }

    public static function run_automation() {
        $settings = get_option( 'aiwb_settings', array() );
        if ( empty( $settings['automation_enabled'] ) || '1' !== $settings['automation_enabled'] ) {
            return;
        }

        $age_days = absint( $settings['automation_post_age_days'] ?? 180 );
        $limit = absint( $settings['automation_post_limit'] ?? 3 );
        $alt_batch = absint( $settings['automation_alt_batch'] ?? 10 );

        AIWB_Content_Updater::auto_update_posts( $age_days, $limit );
        AIWB_Content_Updater::auto_generate_missing_alt( $alt_batch );
        AIWB_Ajax::log_action( 'automation_run', array(
            'age_days' => $age_days,
            'limit' => $limit,
            'alt_batch' => $alt_batch,
        ) );
    }

    public static function run_scheduled_security_scan() {
        AIWB_Ajax::run_full_security_scan();
    }

    public static function log_login_failed( $username ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        AIWB_Ajax::log_action( 'login_failed', array(
            'username' => sanitize_text_field( $username ),
            'ip' => $ip,
        ) );
    }

    public static function log_login_success( $user_login, $user ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        AIWB_Ajax::log_action( 'login_success', array(
            'username' => sanitize_text_field( $user_login ),
            'user_id' => $user ? $user->ID : 0,
            'ip' => $ip,
        ) );
    }

    public static function enforce_firewall() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }
        $ip = AIWB_Health::get_visitor_ip();
        if ( ! $ip ) {
            return;
        }
        if ( AIWB_Health::is_ip_allowed( $ip ) ) {
            return;
        }
        if ( ! AIWB_Health::is_ip_blocked( $ip ) ) {
            return;
        }
        AIWB_Ajax::log_action( 'firewall_block', array(
            'ip' => $ip,
            'message' => 'Blocked by AIWB firewall.',
        ) );
        wp_die( esc_html__( 'Access denied by firewall.', 'ai-ultimate-website-booster' ), esc_html__( 'Access Denied', 'ai-ultimate-website-booster' ), array( 'response' => 403 ) );
    }
}
