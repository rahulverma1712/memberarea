<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Health {

    public function __construct() {
        // Report feedback is surfaced inside scan reports instead of top admin notices.
    }

    public function show_admin_health_notice() {
        if ( ! isset( $_GET['page'] ) || 'aiwb-dashboard' !== $_GET['page'] ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $unused_plugins = $this->find_inactive_plugins();
        if ( count( $unused_plugins ) > 0 ) {
            $message = sprintf( __( 'AI Ultimate Website Booster found %d inactive plugin(s). Review from Plugins > Installed Plugins.', 'ai-ultimate-website-booster' ), count( $unused_plugins ) );
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
    }

    private function find_inactive_plugins() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );
        $inactive = array();

        foreach ( $all_plugins as $path => $plugin ) {
            if ( ! in_array( $path, $active_plugins, true ) ) {
                $inactive[] = $plugin['Name'];
            }
        }

        return $inactive;
    }

    public static function get_large_images( $limit = 10, $min_kb = 500 ) {
        $limit = max( 1, min( 50, absint( $limit ) ) );
        $min_bytes = absint( $min_kb ) * 1024;

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'post_status'    => 'inherit',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $large = array();
        foreach ( $attachments as $attachment ) {
            $path = get_attached_file( $attachment->ID );
            if ( $path && file_exists( $path ) ) {
                $size = filesize( $path );
                if ( $size >= $min_bytes ) {
                    $large[] = array(
                        'id' => $attachment->ID,
                        'title' => $attachment->post_title,
                        'size_kb' => round( $size / 1024 ),
                        'url' => wp_get_attachment_url( $attachment->ID ),
                    );
                }
            }
        }
        return $large;
    }

    public static function get_database_size() {
        global $wpdb;
        $size = 0;
        $db_name = $wpdb->dbname;
        if ( $db_name ) {
            $size = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = %s",
                    $db_name
                )
            );
        }
        return $size ? round( $size / ( 1024 * 1024 ), 2 ) : 0;
    }

    public static function page_speed_tips() {
        return array(
            __( 'Enable caching and use a CDN for faster delivery.', 'ai-ultimate-website-booster' ),
            __( 'Compress large images and serve WebP formats.', 'ai-ultimate-website-booster' ),
            __( 'Minify CSS and JavaScript assets.', 'ai-ultimate-website-booster' ),
            __( 'Reduce unused plugins to lower requests.', 'ai-ultimate-website-booster' ),
        );
    }

    public static function store_health_scan( $scan_type, $results ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_health_scans';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists !== $table ) {
            $charset_collate = $wpdb->get_charset_collate();
            $health_sql = "CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                scan_type varchar(80) NOT NULL,
                results longtext NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) {$charset_collate};";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $health_sql );
        }
        $wpdb->insert(
            $table,
            array(
                'scan_type' => sanitize_text_field( $scan_type ),
                'results' => wp_json_encode( $results ),
            ),
            array( '%s', '%s' )
        );
    }

    public static function get_scan_history( $scan_type, $limit = 6 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_health_scans';
        $limit = absint( $limit );
        if ( $limit < 1 ) {
            $limit = 6;
        }
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT created_at, results FROM {$table} WHERE scan_type = %s ORDER BY created_at DESC LIMIT %d", $scan_type, $limit ),
            ARRAY_A
        );
        $history = array();
        foreach ( array_reverse( $rows ) as $row ) {
            $results = json_decode( $row['results'] ?? '', true );
            $score = isset( $results['security']['score'] ) ? (int) $results['security']['score'] : 0;
            $history[] = array(
                'time' => $row['created_at'],
                'score' => $score,
            );
        }
        return $history;
    }

    public static function get_unused_assets() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );
        $inactive_plugins = array();

        foreach ( $all_plugins as $path => $plugin ) {
            if ( ! in_array( $path, $active_plugins, true ) ) {
                $inactive_plugins[] = array(
                    'name' => $plugin['Name'],
                    'path' => $path,
                );
            }
        }

        $themes = wp_get_themes();
        $active_theme = wp_get_theme();
        $inactive_themes = array();

        foreach ( $themes as $slug => $theme ) {
            if ( $theme->get_stylesheet() !== $active_theme->get_stylesheet() ) {
                $inactive_themes[] = array(
                    'name' => $theme->get( 'Name' ),
                    'slug' => $slug,
                );
            }
        }

        return array(
            'inactive_plugins' => $inactive_plugins,
            'inactive_themes'  => $inactive_themes,
        );
    }

    public static function get_visitor_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return sanitize_text_field( wp_unslash( $ip ) );
    }

    public static function ensure_blocked_ips_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_blocked_ips';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists === $table ) {
            return;
        }
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(64) NOT NULL,
            reason varchar(190) NOT NULL,
            blocked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function ensure_allowlist_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_allowlist_ips';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists === $table ) {
            return;
        }
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(64) NOT NULL,
            reason varchar(190) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function add_allowlist_ip( $ip, $reason = '' ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return new WP_Error( 'invalid_ip', __( 'Invalid IP address.', 'ai-ultimate-website-booster' ) );
        }
        self::ensure_allowlist_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_allowlist_ips';
        $wpdb->insert(
            $table,
            array(
                'ip_address' => $ip,
                'reason' => sanitize_text_field( $reason ),
                'created_by' => get_current_user_id(),
            ),
            array( '%s', '%s', '%d' )
        );
        return true;
    }

    public static function remove_allowlist_ip( $id ) {
        self::ensure_allowlist_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_allowlist_ips';
        $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
        return true;
    }

    public static function get_allowlist_ips( $limit = 50 ) {
        self::ensure_allowlist_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_allowlist_ips';
        $limit = absint( $limit );
        if ( $limit < 1 ) {
            $limit = 50;
        }
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ),
            ARRAY_A
        );
    }

    public static function is_ip_allowed( $ip ) {
        if ( ! $ip ) {
            return false;
        }
        self::ensure_allowlist_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_allowlist_ips';
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT id FROM {$table} WHERE ip_address = %s LIMIT 1", $ip ),
            ARRAY_A
        );
        return ! empty( $row );
    }

    public static function add_blocked_ip( $ip, $reason = '', $duration_hours = 0 ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return new WP_Error( 'invalid_ip', __( 'Invalid IP address.', 'ai-ultimate-website-booster' ) );
        }
        self::ensure_blocked_ips_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_blocked_ips';
        $expires_at = null;
        if ( $duration_hours > 0 ) {
            $expires_at = gmdate( 'Y-m-d H:i:s', time() + ( absint( $duration_hours ) * HOUR_IN_SECONDS ) );
        }
        $wpdb->insert(
            $table,
            array(
                'ip_address' => $ip,
                'reason' => sanitize_text_field( $reason ),
                'expires_at' => $expires_at,
                'created_by' => get_current_user_id(),
            ),
            array( '%s', '%s', '%s', '%d' )
        );
        return true;
    }

    public static function remove_blocked_ip( $id ) {
        self::ensure_blocked_ips_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_blocked_ips';
        $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
        return true;
    }

    public static function get_blocked_ips( $limit = 50 ) {
        self::ensure_blocked_ips_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_blocked_ips';
        $limit = absint( $limit );
        if ( $limit < 1 ) {
            $limit = 50;
        }
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE expires_at IS NOT NULL AND expires_at < %s", gmdate( 'Y-m-d H:i:s' ) ) );
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} ORDER BY blocked_at DESC LIMIT %d", $limit ),
            ARRAY_A
        );
    }

    public static function is_ip_blocked( $ip ) {
        if ( ! $ip ) {
            return false;
        }
        self::ensure_blocked_ips_table();
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_blocked_ips';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE ip_address = %s AND (expires_at IS NULL OR expires_at >= %s) LIMIT 1",
                $ip,
                gmdate( 'Y-m-d H:i:s' )
            ),
            ARRAY_A
        );
        return ! empty( $row );
    }

    public static function get_malware_exclusions() {
        $paths = get_option( 'aiwb_malware_exclusions', array() );
        return is_array( $paths ) ? array_values( array_filter( $paths ) ) : array();
    }

    public static function add_malware_exclusion( $path ) {
        $path = trim( (string) $path );
        if ( $path === '' ) {
            return;
        }
        $paths = self::get_malware_exclusions();
        if ( ! in_array( $path, $paths, true ) ) {
            $paths[] = $path;
        }
        update_option( 'aiwb_malware_exclusions', $paths );
    }

    public static function remove_malware_exclusion( $path ) {
        $paths = self::get_malware_exclusions();
        $paths = array_values( array_filter( $paths, function( $item ) use ( $path ) {
            return $item !== $path;
        } ) );
        update_option( 'aiwb_malware_exclusions', $paths );
    }

    public static function scan_broken_links( $post_count = 10, $link_limit = 50 ) {
        $post_count = max( 1, min( 50, absint( $post_count ) ) );
        $link_limit = max( 5, min( 200, absint( $link_limit ) ) );

        $posts = get_posts( array(
            'posts_per_page' => $post_count,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $links = array();
        foreach ( $posts as $post ) {
            if ( count( $links ) >= $link_limit ) {
                break;
            }
            preg_match_all( '/href=[\\\"\\\']([^\\\"\\\']+)/i', $post->post_content, $matches );
            if ( empty( $matches[1] ) ) {
                continue;
            }
            foreach ( $matches[1] as $url ) {
                if ( count( $links ) >= $link_limit ) {
                    break;
                }
                if ( 0 !== strpos( $url, 'http' ) ) {
                    continue;
                }
                $links[] = array(
                    'url' => esc_url_raw( $url ),
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                );
            }
        }

        $broken = array();
        foreach ( $links as $link ) {
            $response = wp_remote_head( $link['url'], array(
                'timeout' => 5,
                'redirection' => 2,
            ) );
            $status = wp_remote_retrieve_response_code( $response );
            if ( is_wp_error( $response ) || $status >= 400 || 0 === $status ) {
                $broken[] = array(
                    'url' => $link['url'],
                    'status' => $status ? $status : 0,
                    'post_id' => $link['post_id'],
                    'post_title' => $link['post_title'],
                );
            }
        }

        return array(
            'checked' => count( $links ),
            'broken'  => $broken,
        );
    }

    public static function cleanup_database() {
        global $wpdb;

        $expired_transients = delete_expired_transients();
        $revisions = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' LIMIT 50" );
        $deleted_revisions = 0;
        if ( $revisions ) {
            foreach ( $revisions as $revision_id ) {
                if ( wp_delete_post_revision( $revision_id ) ) {
                    $deleted_revisions++;
                }
            }
        }

        return array(
            'expired_transients_cleared' => (int) $expired_transients,
            'revisions_deleted' => $deleted_revisions,
        );
    }

    public static function security_checks() {
        $checks = array();
        $score = 100;

        $ssl = is_ssl();
        if ( ! $ssl ) {
            $score -= 8;
        }
        $checks[] = array(
            'label' => __( 'SSL Enabled', 'ai-ultimate-website-booster' ),
            'status' => $ssl ? 'pass' : 'warn',
            'detail' => $ssl ? __( 'Site is served over HTTPS.', 'ai-ultimate-website-booster' ) : __( 'Enable SSL to protect visitors and logins.', 'ai-ultimate-website-booster' ),
        );

        $xmlrpc_enabled = apply_filters( 'xmlrpc_enabled', true );
        if ( $xmlrpc_enabled ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'XML-RPC Disabled', 'ai-ultimate-website-booster' ),
            'status' => $xmlrpc_enabled ? 'warn' : 'pass',
            'detail' => $xmlrpc_enabled ? __( 'XML-RPC is enabled. Consider disabling if not used.', 'ai-ultimate-website-booster' ) : __( 'XML-RPC is disabled.', 'ai-ultimate-website-booster' ),
        );

        $file_edit = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
        if ( ! $file_edit ) {
            $score -= 5;
        }
        $checks[] = array(
            'label' => __( 'File Editor Disabled', 'ai-ultimate-website-booster' ),
            'status' => $file_edit ? 'pass' : 'warn',
            'detail' => $file_edit ? __( 'File editor is disabled in admin.', 'ai-ultimate-website-booster' ) : __( 'Disable file editor to reduce risk.', 'ai-ultimate-website-booster' ),
        );

        $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
        if ( $debug ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'Debug Mode', 'ai-ultimate-website-booster' ),
            'status' => $debug ? 'warn' : 'pass',
            'detail' => $debug ? __( 'WP_DEBUG is enabled. Turn it off on production.', 'ai-ultimate-website-booster' ) : __( 'Debug mode is disabled.', 'ai-ultimate-website-booster' ),
        );

        $admin_user = get_user_by( 'login', 'admin' );
        if ( $admin_user ) {
            $score -= 4;
        }
        $checks[] = array(
            'label' => __( 'Default Admin Username', 'ai-ultimate-website-booster' ),
            'status' => $admin_user ? 'warn' : 'pass',
            'detail' => $admin_user ? __( 'A user with username "admin" exists. Consider changing.', 'ai-ultimate-website-booster' ) : __( 'No default admin username found.', 'ai-ultimate-website-booster' ),
        );

        global $wpdb;
        $prefix_default = isset( $wpdb->prefix ) && 'wp_' === $wpdb->prefix;
        if ( $prefix_default ) {
            $score -= 4;
        }
        $checks[] = array(
            'label' => __( 'Database Prefix', 'ai-ultimate-website-booster' ),
            'status' => $prefix_default ? 'warn' : 'pass',
            'detail' => $prefix_default ? __( 'Database prefix is default (wp_). Consider changing for security.', 'ai-ultimate-website-booster' ) : __( 'Database prefix is customized.', 'ai-ultimate-website-booster' ),
        );

        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        $core_updates = get_core_updates();
        $core_outdated = false;
        if ( is_array( $core_updates ) ) {
            foreach ( $core_updates as $update ) {
                if ( isset( $update->response ) && 'upgrade' === $update->response ) {
                    $core_outdated = true;
                    break;
                }
            }
        }
        if ( $core_outdated ) {
            $score -= 10;
        }
        $checks[] = array(
            'label' => __( 'WordPress Core Updates', 'ai-ultimate-website-booster' ),
            'status' => $core_outdated ? 'warn' : 'pass',
            'detail' => $core_outdated ? __( 'Core update available. Update WordPress.', 'ai-ultimate-website-booster' ) : __( 'WordPress core is up to date.', 'ai-ultimate-website-booster' ),
        );

        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        $plugin_updates = get_plugin_updates();
        $plugin_outdated = ! empty( $plugin_updates );
        if ( $plugin_outdated ) {
            $score -= 8;
        }
        $checks[] = array(
            'label' => __( 'Plugin Updates', 'ai-ultimate-website-booster' ),
            'status' => $plugin_outdated ? 'warn' : 'pass',
            'detail' => $plugin_outdated ? __( 'Some plugins have updates available.', 'ai-ultimate-website-booster' ) : __( 'All plugins are up to date.', 'ai-ultimate-website-booster' ),
        );

        if ( ! function_exists( 'get_theme_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        $theme_updates = get_theme_updates();
        $theme_outdated = ! empty( $theme_updates );
        if ( $theme_outdated ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'Theme Updates', 'ai-ultimate-website-booster' ),
            'status' => $theme_outdated ? 'warn' : 'pass',
            'detail' => $theme_outdated ? __( 'Some themes have updates available.', 'ai-ultimate-website-booster' ) : __( 'All themes are up to date.', 'ai-ultimate-website-booster' ),
        );

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );
        $active_names = array();
        foreach ( $active_plugins as $path ) {
            if ( isset( $all_plugins[ $path ] ) ) {
                $active_names[] = strtolower( $all_plugins[ $path ]['Name'] );
                $active_names[] = strtolower( $path );
            }
        }

        $waf_plugins = array( 'wordfence', 'sucuri', 'ithemes security', 'all in one wp security', 'malcare', 'aios', 'jetpack' );
        $waf_found = false;
        foreach ( $waf_plugins as $needle ) {
            foreach ( $active_names as $name ) {
                if ( strpos( $name, $needle ) !== false ) {
                    $waf_found = true;
                    break 2;
                }
            }
        }
        if ( ! $waf_found ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'WAF / Firewall Detected', 'ai-ultimate-website-booster' ),
            'status' => $waf_found ? 'pass' : 'warn',
            'detail' => $waf_found ? __( 'A security/firewall plugin is active.', 'ai-ultimate-website-booster' ) : __( 'No firewall plugin detected. Consider enabling a WAF.', 'ai-ultimate-website-booster' ),
        );

        $bruteforce_plugins = array( 'limit login attempts', 'limit login attempts reloaded', 'wordfence', 'ithemes security', 'aios', 'wp limit login', 'loginizer' );
        $bruteforce_found = false;
        foreach ( $bruteforce_plugins as $needle ) {
            foreach ( $active_names as $name ) {
                if ( strpos( $name, $needle ) !== false ) {
                    $bruteforce_found = true;
                    break 2;
                }
            }
        }
        if ( ! $bruteforce_found ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'Brute-force Protection', 'ai-ultimate-website-booster' ),
            'status' => $bruteforce_found ? 'pass' : 'warn',
            'detail' => $bruteforce_found ? __( 'Login throttling protection detected.', 'ai-ultimate-website-booster' ) : __( 'No login throttling detected. Enable brute-force protection.', 'ai-ultimate-website-booster' ),
        );

        $headers = wp_remote_head( home_url(), array( 'timeout' => 8 ) );
        $header_list = array();
        if ( ! is_wp_error( $headers ) ) {
            $header_list = wp_remote_retrieve_headers( $headers );
        }
        $has_xfo = isset( $header_list['x-frame-options'] );
        $has_csp = isset( $header_list['content-security-policy'] );
        $has_hsts = isset( $header_list['strict-transport-security'] );
        $header_ok = $has_xfo && $has_hsts;
        if ( ! $header_ok ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ),
            'status' => $header_ok ? 'pass' : 'warn',
            'detail' => sprintf( __( 'XFO: %s, CSP: %s, HSTS: %s', 'ai-ultimate-website-booster' ), $has_xfo ? 'ON' : 'OFF', $has_csp ? 'ON' : 'OFF', $has_hsts ? 'ON' : 'OFF' ),
        );

        $paths = array(
            'wp-content' => WP_CONTENT_DIR,
            'uploads' => wp_get_upload_dir()['basedir'] ?? '',
            'plugins' => WP_PLUGIN_DIR,
        );
        $perm_warn = false;
        $perm_details = array();
        foreach ( $paths as $label => $path ) {
            if ( ! $path || ! file_exists( $path ) ) {
                continue;
            }
            $perms = substr( sprintf( '%o', fileperms( $path ) ), -3 );
            $perm_details[] = $label . ':' . $perms;
            if ( (int) $perms > 775 ) {
                $perm_warn = true;
            }
        }
        if ( $perm_warn ) {
            $score -= 5;
        }
        $checks[] = array(
            'label' => __( 'File Permissions (wp-content/uploads/plugins)', 'ai-ultimate-website-booster' ),
            'status' => $perm_warn ? 'warn' : 'pass',
            'detail' => $perm_details ? implode( ' | ', $perm_details ) : __( 'Unable to read permissions.', 'ai-ultimate-website-booster' ),
        );

        $backup_plugins = array( 'updraftplus', 'backupbuddy', 'backwpup', 'duplicator', 'wpvivid', 'all-in-one wp migration', 'vaultpress' );
        $backup_found = false;
        foreach ( $backup_plugins as $needle ) {
            foreach ( $active_names as $name ) {
                if ( strpos( $name, $needle ) !== false ) {
                    $backup_found = true;
                    break 2;
                }
            }
        }
        if ( ! $backup_found ) {
            $score -= 4;
        }
        $checks[] = array(
            'label' => __( 'Backup Plugin Detected', 'ai-ultimate-website-booster' ),
            'status' => $backup_found ? 'pass' : 'warn',
            'detail' => $backup_found ? __( 'Backup plugin is active.', 'ai-ultimate-website-booster' ) : __( 'No backup plugin detected.', 'ai-ultimate-website-booster' ),
        );

        $core_integrity = self::core_integrity_check();
        if ( $core_integrity['issues'] > 0 ) {
            $score -= 10;
        }
        $checks[] = array(
            'label' => __( 'Core File Integrity', 'ai-ultimate-website-booster' ),
            'status' => $core_integrity['issues'] > 0 ? 'warn' : 'pass',
            'detail' => $core_integrity['issues'] > 0 ? sprintf( __( '%d core file anomalies detected.', 'ai-ultimate-website-booster' ), $core_integrity['issues'] ) : __( 'Core file checksums look clean.', 'ai-ultimate-website-booster' ),
        );

        $php_version = PHP_VERSION;
        $php_ok = version_compare( $php_version, '8.0', '>=' );
        if ( ! $php_ok ) {
            $score -= 6;
        }
        $checks[] = array(
            'label' => __( 'PHP Version', 'ai-ultimate-website-booster' ),
            'status' => $php_ok ? 'pass' : 'warn',
            'detail' => $php_ok ? sprintf( __( 'PHP %s is OK.', 'ai-ultimate-website-booster' ), $php_version ) : sprintf( __( 'PHP %s is outdated. Upgrade recommended.', 'ai-ultimate-website-booster' ), $php_version ),
        );

        $wp_version = get_bloginfo( 'version' );
        $checks[] = array(
            'label' => __( 'WordPress Version', 'ai-ultimate-website-booster' ),
            'status' => $core_outdated ? 'warn' : 'pass',
            'detail' => sprintf( __( 'Current version: %s.', 'ai-ultimate-website-booster' ), $wp_version ),
        );

        $auto_updates = ( defined( 'WP_AUTO_UPDATE_CORE' ) && WP_AUTO_UPDATE_CORE );
        if ( ! $auto_updates ) {
            $score -= 3;
        }
        $checks[] = array(
            'label' => __( 'Auto Updates', 'ai-ultimate-website-booster' ),
            'status' => $auto_updates ? 'pass' : 'warn',
            'detail' => $auto_updates ? __( 'Core auto-updates are enabled.', 'ai-ultimate-website-booster' ) : __( 'Enable core auto-updates for security.', 'ai-ultimate-website-booster' ),
        );

        $config_path = ABSPATH . 'wp-config.php';
        if ( ! file_exists( $config_path ) ) {
            $config_path = dirname( ABSPATH ) . '/wp-config.php';
        }
        $config_perms = file_exists( $config_path ) ? substr( sprintf( '%o', fileperms( $config_path ) ), -3 ) : '';
        $config_ok = $config_perms && (int) $config_perms <= 640;
        if ( $config_perms && ! $config_ok ) {
            $score -= 5;
        }
        $checks[] = array(
            'label' => __( 'wp-config.php Permissions', 'ai-ultimate-website-booster' ),
            'status' => $config_ok ? 'pass' : 'warn',
            'detail' => $config_perms ? sprintf( __( 'Permissions: %s.', 'ai-ultimate-website-booster' ), $config_perms ) : __( 'wp-config.php not found.', 'ai-ultimate-website-booster' ),
        );

        $htaccess = ABSPATH . '.htaccess';
        $ht_ok = ! file_exists( $htaccess ) || (int) substr( sprintf( '%o', fileperms( $htaccess ) ), -3 ) <= 644;
        if ( file_exists( $htaccess ) && ! $ht_ok ) {
            $score -= 3;
        }
        $checks[] = array(
            'label' => __( '.htaccess Permissions', 'ai-ultimate-website-booster' ),
            'status' => $ht_ok ? 'pass' : 'warn',
            'detail' => file_exists( $htaccess ) ? sprintf( __( 'Permissions: %s.', 'ai-ultimate-website-booster' ), substr( sprintf( '%o', fileperms( $htaccess ) ), -3 ) ) : __( 'File not present (OK on Nginx).', 'ai-ultimate-website-booster' ),
        );

        $uploads = wp_get_upload_dir();
        $uploads_writable = $uploads && isset( $uploads['basedir'] ) ? wp_is_writable( $uploads['basedir'] ) : false;
        $checks[] = array(
            'label' => __( 'Uploads Writable', 'ai-ultimate-website-booster' ),
            'status' => $uploads_writable ? 'pass' : 'warn',
            'detail' => $uploads_writable ? __( 'Uploads directory is writable.', 'ai-ultimate-website-booster' ) : __( 'Uploads directory is not writable.', 'ai-ultimate-website-booster' ),
        );

        $admin_email = get_option( 'admin_email' );
        $email_ok = is_email( $admin_email );
        $checks[] = array(
            'label' => __( 'Admin Email', 'ai-ultimate-website-booster' ),
            'status' => $email_ok ? 'pass' : 'warn',
            'detail' => $email_ok ? sprintf( __( 'Admin email: %s.', 'ai-ultimate-website-booster' ), $admin_email ) : __( 'Admin email is invalid.', 'ai-ultimate-website-booster' ),
        );

        $inactive_users = count( get_users( array(
            'number' => 5,
            'orderby' => 'registered',
            'order' => 'ASC',
            'fields' => 'ID',
        ) ) );
        if ( $inactive_users > 0 ) {
            $score -= 2;
        }
        $checks[] = array(
            'label' => __( 'Old Users Review', 'ai-ultimate-website-booster' ),
            'status' => $inactive_users > 0 ? 'warn' : 'pass',
            'detail' => __( 'Review old/inactive accounts regularly.', 'ai-ultimate-website-booster' ),
        );

        $score = max( 0, min( 100, $score ) );

        return array(
            'score' => $score,
            'checks' => $checks,
        );
    }

    public static function module_scan( $module ) {
        $map = array(
            'login' => array(
                'label' => __( 'Login Security', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'Brute-force Protection', 'ai-ultimate-website-booster' ),
                    __( 'Default Admin Username', 'ai-ultimate-website-booster' ),
                    __( 'Admin Email', 'ai-ultimate-website-booster' ),
                ),
            ),
            'firewall' => array(
                'label' => __( 'Firewall', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'WAF / Firewall Detected', 'ai-ultimate-website-booster' ),
                    __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ),
                ),
            ),
            'integrity' => array(
                'label' => __( 'File Integrity', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'Core File Integrity', 'ai-ultimate-website-booster' ),
                    __( 'File Permissions (wp-content/uploads/plugins)', 'ai-ultimate-website-booster' ),
                    __( 'wp-config.php Permissions', 'ai-ultimate-website-booster' ),
                    __( '.htaccess Permissions', 'ai-ultimate-website-booster' ),
                ),
            ),
            'malware' => array(
                'label' => __( 'Malware Scanner', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'Core File Integrity', 'ai-ultimate-website-booster' ),
                    __( 'Plugin Updates', 'ai-ultimate-website-booster' ),
                    __( 'Theme Updates', 'ai-ultimate-website-booster' ),
                ),
                'malware' => true,
            ),
            'hardening' => array(
                'label' => __( 'Hardening', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'XML-RPC Disabled', 'ai-ultimate-website-booster' ),
                    __( 'File Editor Disabled', 'ai-ultimate-website-booster' ),
                    __( 'Debug Mode', 'ai-ultimate-website-booster' ),
                    __( 'Auto Updates', 'ai-ultimate-website-booster' ),
                ),
            ),
            'headers' => array(
                'label' => __( 'Security Headers', 'ai-ultimate-website-booster' ),
                'checks' => array(
                    __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ),
                    __( 'SSL Enabled', 'ai-ultimate-website-booster' ),
                ),
            ),
        );

        if ( empty( $map[ $module ] ) ) {
            return array();
        }

        $all = self::security_checks();
        $filtered = array();
        foreach ( $all['checks'] as $check ) {
            if ( in_array( $check['label'], $map[ $module ]['checks'], true ) ) {
                $filtered[] = $check;
            }
        }

        $pass = 0;
        $warn = 0;
        foreach ( $filtered as $check ) {
            if ( $check['status'] === 'pass' ) {
                $pass++;
            } else {
                $warn++;
            }
        }
        $total = max( 1, $pass + $warn );
        $score = (int) round( ( $pass / $total ) * 100 );

        $report = array(
            'module' => $module,
            'module_label' => $map[ $module ]['label'],
            'security' => array(
                'score' => $score,
                'checks' => $filtered,
            ),
            'broken_links' => array(
                'checked' => 0,
                'broken' => array(),
            ),
            'unused_assets' => array(
                'inactive_plugins' => array(),
                'inactive_themes' => array(),
            ),
            'database_size_mb' => self::get_database_size(),
            'large_images' => array(),
            'speed_tips' => array(),
            'timestamp' => current_time( 'mysql' ),
        );

        if ( ! empty( $map[ $module ]['malware'] ) ) {
            $report['malware_scan'] = self::quick_malware_scan( 200 );
        }

        return $report;
    }

    public static function core_integrity_check() {
        $issues = 0;
        $sample = array();

        if ( ! function_exists( 'wp_get_core_checksums' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        if ( ! function_exists( 'wp_get_core_checksums' ) ) {
            return array( 'issues' => 0, 'sample' => array() );
        }

        $checksums = wp_get_core_checksums();
        if ( ! is_array( $checksums ) ) {
            return array( 'issues' => 0, 'sample' => array() );
        }

        foreach ( $checksums as $file => $hash ) {
            $path = ABSPATH . $file;
            if ( ! file_exists( $path ) ) {
                $issues++;
                if ( count( $sample ) < 5 ) {
                    $sample[] = $file;
                }
                continue;
            }
            $current = md5_file( $path );
            if ( $current && $current !== $hash ) {
                $issues++;
                if ( count( $sample ) < 5 ) {
                    $sample[] = $file;
                }
            }
        }

        return array(
            'issues' => $issues,
            'sample' => $sample,
        );
    }

    public static function build_integrity_baseline( $max_files = 200 ) {
        $baseline = array(
            'timestamp' => current_time( 'mysql' ),
            'files' => array(),
            'source' => 'files',
        );
        if ( function_exists( 'wp_get_core_checksums' ) ) {
            $checksums = wp_get_core_checksums();
            if ( is_array( $checksums ) ) {
                $baseline['source'] = 'checksums';
                $count = 0;
                foreach ( $checksums as $file => $hash ) {
                    $baseline['files'][ $file ] = $hash;
                    $count++;
                    if ( $count >= $max_files ) {
                        break;
                    }
                }
                update_option( 'aiwb_integrity_baseline', $baseline );
                return $baseline;
            }
        }

        $dirs = array( ABSPATH . 'wp-admin', ABSPATH . 'wp-includes' );
        $count = 0;
        foreach ( $dirs as $dir ) {
            if ( ! is_dir( $dir ) ) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
            );
            foreach ( $iterator as $file ) {
                if ( $count >= $max_files ) {
                    break 2;
                }
                if ( ! $file->isFile() ) {
                    continue;
                }
                $ext = strtolower( $file->getExtension() );
                if ( ! in_array( $ext, array( 'php', 'js', 'css' ), true ) ) {
                    continue;
                }
                $path = $file->getPathname();
                $hash = md5_file( $path );
                if ( $hash ) {
                    $baseline['files'][ str_replace( ABSPATH, '', $path ) ] = $hash;
                    $count++;
                }
            }
        }
        update_option( 'aiwb_integrity_baseline', $baseline );
        return $baseline;
    }

    public static function integrity_scan( $max_files = 200 ) {
        $baseline = get_option( 'aiwb_integrity_baseline', array() );
        if ( empty( $baseline['files'] ) ) {
            return array(
                'status' => 'warn',
                'message' => __( 'Baseline not built yet.', 'ai-ultimate-website-booster' ),
                'issues' => array(),
            );
        }

        $issues = array();
        $count = 0;
        foreach ( $baseline['files'] as $file => $hash ) {
            if ( $count >= $max_files ) {
                break;
            }
            $path = ABSPATH . $file;
            if ( ! file_exists( $path ) ) {
                $issues[] = $file . ' (missing)';
                $count++;
                continue;
            }
            $current = md5_file( $path );
            if ( $current && $current !== $hash ) {
                $issues[] = $file . ' (modified)';
                $count++;
            }
        }

        return array(
            'status' => empty( $issues ) ? 'pass' : 'warn',
            'message' => empty( $issues ) ? __( 'No integrity issues detected.', 'ai-ultimate-website-booster' ) : __( 'Integrity issues detected.', 'ai-ultimate-website-booster' ),
            'issues' => $issues,
        );
    }

    public static function quick_malware_scan( $max_files = 350 ) {
        $patterns = array(
            '/\\beval\\s*\\(/i',
            '/\\bbase64_decode\\s*\\(/i',
            '/\\bgzinflate\\s*\\(/i',
            '/\\b(shell_exec|passthru|system|exec)\\s*\\(/i',
            '/\\bassert\\s*\\(/i',
            '/preg_replace\\s*\\(.*\\/e[\'"]/i',
        );

        $dirs = array( WP_CONTENT_DIR . '/plugins', WP_CONTENT_DIR . '/themes' );
        $exclusions = self::get_malware_exclusions();
        $allowed_ext = array( 'php', 'js' );
        $files_scanned = 0;
        $findings = 0;
        $samples = array();

        foreach ( $dirs as $dir ) {
            if ( ! is_dir( $dir ) ) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
            );
            foreach ( $iterator as $file ) {
                if ( $files_scanned >= $max_files ) {
                    break 2;
                }
                if ( ! $file->isFile() ) {
                    continue;
                }
                $ext = strtolower( $file->getExtension() );
                if ( ! in_array( $ext, $allowed_ext, true ) ) {
                    continue;
                }
                if ( $file->getSize() > 350 * 1024 ) {
                    continue;
                }

                $path = $file->getPathname();
                $skip = false;
                foreach ( $exclusions as $exclude ) {
                    $exclude = trim( $exclude );
                    if ( $exclude === '' ) {
                        continue;
                    }
                    $needle = $exclude;
                    if ( strpos( $exclude, '/' ) !== 0 && strpos( $exclude, '\\' ) !== 0 ) {
                        $needle = WP_CONTENT_DIR . '/' . ltrim( $exclude, '/\\' );
                    }
                    if ( stripos( $path, $needle ) !== false ) {
                        $skip = true;
                        break;
                    }
                }
                if ( $skip ) {
                    continue;
                }

                $files_scanned++;
                $content = @file_get_contents( $path );
                if ( $content === false ) {
                    continue;
                }
                foreach ( $patterns as $pattern ) {
                    if ( preg_match( $pattern, $content ) ) {
                        $findings++;
                        if ( count( $samples ) < 5 ) {
                            $samples[] = str_replace( ABSPATH, '', $path );
                        }
                        break;
                    }
                }
            }
        }

        return array(
            'files_scanned' => $files_scanned,
            'findings' => $findings,
            'samples' => $samples,
        );
    }

    public static function count_action( $action, $days = 7 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_action_logs';
        $days = absint( $days );
        $since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE action_name = %s AND created_at >= %s",
                $action,
                $since
            )
        );
    }

    public static function recent_actions( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_action_logs';
        $limit = absint( $limit );
        if ( $limit < 1 ) {
            $limit = 20;
        }
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ),
            ARRAY_A
        );
    }
}

new AIWB_Health();
