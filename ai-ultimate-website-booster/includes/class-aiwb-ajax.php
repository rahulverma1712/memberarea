<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_aiwb_generate_content', array( $this, 'generate_content' ) );
        add_action( 'wp_ajax_aiwb_blog_ideas', array( $this, 'blog_ideas' ) );
        add_action( 'wp_ajax_aiwb_health_scan_links', array( $this, 'health_scan_links' ) );
        add_action( 'wp_ajax_aiwb_health_cleanup_db', array( $this, 'health_cleanup_db' ) );
        add_action( 'wp_ajax_aiwb_health_unused_assets', array( $this, 'health_unused_assets' ) );
        add_action( 'wp_ajax_aiwb_scan_old_posts', array( $this, 'scan_old_posts' ) );
        add_action( 'wp_ajax_aiwb_rewrite_posts', array( $this, 'rewrite_posts' ) );
        add_action( 'wp_ajax_aiwb_generate_missing_alt', array( $this, 'generate_missing_alt' ) );
        add_action( 'wp_ajax_aiwb_create_post', array( $this, 'create_post' ) );
        add_action( 'wp_ajax_aiwb_bulk_generate', array( $this, 'bulk_generate' ) );
        add_action( 'wp_ajax_aiwb_bulk_save', array( $this, 'bulk_save' ) );
        add_action( 'wp_ajax_aiwb_clear_schedule_reminder', array( $this, 'clear_schedule_reminder' ) );
        add_action( 'wp_ajax_aiwb_schedule_now', array( $this, 'schedule_now' ) );
        add_action( 'wp_ajax_aiwb_preview_update', array( $this, 'preview_update' ) );
        add_action( 'wp_ajax_aiwb_publish_update', array( $this, 'publish_update' ) );
        add_action( 'wp_ajax_aiwb_version_list', array( $this, 'version_list' ) );
        add_action( 'wp_ajax_aiwb_version_rollback', array( $this, 'version_rollback' ) );
        add_action( 'wp_ajax_aiwb_health_large_images', array( $this, 'health_large_images' ) );
        add_action( 'wp_ajax_aiwb_health_db_size', array( $this, 'health_db_size' ) );
        add_action( 'wp_ajax_aiwb_health_speed_tips', array( $this, 'health_speed_tips' ) );
        add_action( 'wp_ajax_aiwb_save_popup', array( $this, 'save_popup' ) );
        add_action( 'wp_ajax_aiwb_get_popups', array( $this, 'get_popups' ) );
        add_action( 'wp_ajax_aiwb_get_popup', array( $this, 'get_popup' ) );
        add_action( 'wp_ajax_aiwb_delete_popup', array( $this, 'delete_popup' ) );
        add_action( 'wp_ajax_aiwb_dashboard_data', array( $this, 'dashboard_data' ) );
        add_action( 'wp_ajax_aiwb_save_seo_meta', array( $this, 'save_seo_meta' ) );
        add_action( 'wp_ajax_aiwb_get_seo_post', array( $this, 'get_seo_post' ) );
        add_action( 'wp_ajax_aiwb_get_posts_list', array( $this, 'get_posts_list' ) );
        add_action( 'wp_ajax_aiwb_create_seo_draft', array( $this, 'create_seo_draft' ) );
        add_action( 'wp_ajax_aiwb_generate_image', array( $this, 'generate_image' ) );
        add_action( 'wp_ajax_aiwb_image_search', array( $this, 'image_search' ) );
        add_action( 'wp_ajax_aiwb_image_attach', array( $this, 'image_attach' ) );
        add_action( 'wp_ajax_aiwb_create_category', array( $this, 'create_category' ) );
        add_action( 'wp_ajax_aiwb_get_categories', array( $this, 'get_categories' ) );
        add_action( 'wp_ajax_aiwb_security_scan_all', array( $this, 'security_scan_all' ) );
        add_action( 'wp_ajax_aiwb_security_scan_module', array( $this, 'security_scan_module' ) );
        add_action( 'wp_ajax_aiwb_export_security_csv', array( $this, 'export_security_csv' ) );
        add_action( 'wp_ajax_aiwb_export_security_pdf', array( $this, 'export_security_pdf' ) );
        add_action( 'wp_ajax_aiwb_firewall_block_ip', array( $this, 'firewall_block_ip' ) );
        add_action( 'wp_ajax_aiwb_firewall_unblock_ip', array( $this, 'firewall_unblock_ip' ) );
        add_action( 'wp_ajax_aiwb_firewall_save_settings', array( $this, 'firewall_save_settings' ) );
        add_action( 'wp_ajax_aiwb_allowlist_add', array( $this, 'allowlist_add' ) );
        add_action( 'wp_ajax_aiwb_allowlist_remove', array( $this, 'allowlist_remove' ) );
        add_action( 'wp_ajax_aiwb_clear_logs', array( $this, 'clear_logs' ) );
        add_action( 'wp_ajax_aiwb_malware_add_exclusion', array( $this, 'malware_add_exclusion' ) );
        add_action( 'wp_ajax_aiwb_malware_remove_exclusion', array( $this, 'malware_remove_exclusion' ) );
        add_action( 'wp_ajax_aiwb_integrity_rebuild', array( $this, 'integrity_rebuild' ) );
        add_action( 'wp_ajax_aiwb_integrity_scan', array( $this, 'integrity_scan' ) );
        add_action( 'wp_ajax_aiwb_export_module_csv', array( $this, 'export_module_csv' ) );
        add_action( 'wp_ajax_aiwb_export_module_pdf', array( $this, 'export_module_pdf' ) );
        add_action( 'wp_ajax_aiwb_export_security_logs_csv', array( $this, 'export_security_logs_csv' ) );
        add_action( 'wp_ajax_aiwb_export_security_logs_pdf', array( $this, 'export_security_logs_pdf' ) );
        add_action( 'wp_ajax_aiwb_save_security_schedule', array( $this, 'save_security_schedule' ) );
    }

    private function ensure_popup_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_popups';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists === $table ) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $popup_sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(120) NOT NULL,
            popup_type varchar(80) NOT NULL,
            settings_json longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $popup_sql );
    }

    private static function ensure_action_log_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_action_logs';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists === $table ) {
            return;
        }
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action_name varchar(120) NOT NULL,
            action_data text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function generate_content() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $topic = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
        $tone  = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'professional' ) );

        if ( empty( $topic ) ) {
            wp_send_json_error( array( 'message' => __( 'Topic is required.', 'ai-ultimate-website-booster' ) ) );
        }

        $content = AIWB_AI::generate_content( $topic, $tone );
        self::log_action( 'generate_content', array( 'topic' => $topic, 'tone' => $tone ) );

        wp_send_json_success( array( 'content' => $content ) );
    }

    public function blog_ideas() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
        $count = absint( $_POST['count'] ?? 20 );
        if ( empty( $keyword ) ) {
            wp_send_json_error( array( 'message' => __( 'Keyword is required.', 'ai-ultimate-website-booster' ) ) );
        }
        if ( ! $count ) {
            $count = 20;
        }

        $ideas = AIWB_AI::generate_blog_ideas( $keyword, $count );
        self::log_action( 'blog_ideas', array( 'keyword' => $keyword, 'count' => $count ) );

        wp_send_json_success( array( 'ideas' => $ideas ) );
    }

    public function health_scan_links() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $post_count = absint( $_POST['post_count'] ?? 10 );
        $link_limit = absint( $_POST['link_limit'] ?? 50 );
        $results = AIWB_Health::scan_broken_links( $post_count, $link_limit );
        self::log_action( 'scan_broken_links', array( 'post_count' => $post_count, 'link_limit' => $link_limit ) );

        wp_send_json_success( $results );
    }

    public function health_cleanup_db() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $results = AIWB_Health::cleanup_database();
        self::log_action( 'cleanup_database', $results );
        wp_send_json_success( $results );
    }

    public function health_unused_assets() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $results = AIWB_Health::get_unused_assets();
        wp_send_json_success( $results );
    }

    public function scan_old_posts() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $age_days = absint( $_POST['age_days'] ?? 180 );
        $limit = absint( $_POST['limit'] ?? 10 );
        $results = AIWB_Content_Updater::scan_old_posts( $age_days, $limit );
        wp_send_json_success( $results );
    }

    public function rewrite_posts() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $ids = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : array();
        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );
        $tone = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'professional' ) );

        if ( empty( $ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Select at least one post.', 'ai-ultimate-website-booster' ) ) );
        }

        $results = AIWB_Content_Updater::rewrite_posts( $ids, $tone );
        self::log_action( 'rewrite_posts', $results );
        wp_send_json_success( $results );
    }

    public function generate_missing_alt() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $limit = absint( $_POST['limit'] ?? 10 );
        $results = AIWB_Content_Updater::generate_missing_alt( $limit );
        self::log_action( 'generate_missing_alt', $results );
        wp_send_json_success( $results );
    }

    public function create_post() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
        $content = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
        $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) );
        $date = sanitize_text_field( wp_unslash( $_POST['schedule'] ?? '' ) );
        $category = absint( $_POST['category'] ?? 0 );
        $tags = sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) );
        $featured_id = absint( $_POST['featured_id'] ?? 0 );

        if ( empty( $title ) ) {
            wp_send_json_error( array( 'message' => __( 'Post title is required.', 'ai-ultimate-website-booster' ) ) );
        }

        if ( 'schedule' === $status ) {
            $status = 'future';
        } elseif ( ! in_array( $status, array( 'draft', 'publish', 'future' ), true ) ) {
            $status = 'draft';
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => $status,
            'post_type'    => 'post',
            'post_date'    => $date ? $date : current_time( 'mysql' ),
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Unable to create post.', 'ai-ultimate-website-booster' ) ) );
        }

        if ( $category ) {
            wp_set_post_categories( $post_id, array( $category ) );
        }
        if ( $tags ) {
            wp_set_post_tags( $post_id, $tags );
        }
        if ( $featured_id ) {
            set_post_thumbnail( $post_id, $featured_id );
        }

        self::log_action( 'create_post', array( 'post_id' => $post_id ) );
        wp_send_json_success( array( 'post_id' => $post_id, 'edit_url' => get_edit_post_link( $post_id, '' ) ) );
    }

    public function bulk_generate() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $keywords_raw = sanitize_textarea_field( wp_unslash( $_POST['keywords'] ?? '' ) );
        $count = absint( $_POST['count'] ?? 5 );
        $tone = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'professional' ) );

        if ( empty( $keywords_raw ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter keywords.', 'ai-ultimate-website-booster' ) ) );
        }

        $lines = preg_split( "/\\r\\n|\\r|\\n/", $keywords_raw );
        $lines = array_filter( array_map( 'trim', $lines ) );
        $keywords = array();
        foreach ( $lines as $line ) {
            $parts = preg_split( '/\\s*,\\s*/', $line );
            foreach ( $parts as $part ) {
                $part = trim( $part );
                $part = preg_replace( '/^\\d+[\\)\\.\\-\\s]+/', '', $part );
                if ( $part ) {
                    $keywords[] = $part;
                }
            }
        }
        if ( empty( $keywords ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter keywords.', 'ai-ultimate-website-booster' ) ) );
        }
        $keywords = array_values( array_unique( $keywords ) );
        $keywords = array_slice( $keywords, 0, $count );

        $items = array();
        foreach ( $keywords as $keyword ) {
            $content = AIWB_AI::generate_content( $keyword, $tone );
            $items[] = array(
                'title' => $keyword,
                'keyword' => $keyword,
                'content' => $content,
            );
        }

        self::log_action( 'bulk_generate', array( 'count' => count( $items ) ) );
        wp_send_json_success( array(
            'items' => $items,
            'requested' => count( $keywords ),
        ) );
    }

    public function bulk_save() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $posts_raw = wp_unslash( $_POST['posts'] ?? '' );
        $posts = is_string( $posts_raw ) ? json_decode( $posts_raw, true ) : array();
        $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) );
        $schedule = sanitize_text_field( wp_unslash( $_POST['schedule'] ?? '' ) );
        $auto_image = sanitize_text_field( wp_unslash( $_POST['auto_image'] ?? '0' ) ) === '1';
        $save_reminder = sanitize_text_field( wp_unslash( $_POST['save_reminder'] ?? '0' ) ) === '1';

        if ( ! in_array( $status, array( 'draft', 'publish', 'schedule' ), true ) ) {
            $status = 'draft';
        }
        if ( empty( $posts ) || ! is_array( $posts ) ) {
            wp_send_json_error( array( 'message' => __( 'No posts to save.', 'ai-ultimate-website-booster' ) ) );
        }

        $created = array();
        $image_success = 0;
        $image_failed = 0;
        $schedule_date = '';
        $schedule_ts = 0;
        if ( 'schedule' === $status && ! empty( $schedule ) ) {
            $timestamp = strtotime( $schedule );
            if ( $timestamp ) {
                $schedule_ts = $timestamp;
                $schedule_date = date( 'Y-m-d H:i:s', $timestamp );
            }
        }
        if ( 'schedule' === $status && empty( $schedule_date ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid schedule date.', 'ai-ultimate-website-booster' ) ) );
        }
        if ( 'schedule' === $status && $schedule_ts && $schedule_ts <= current_time( 'timestamp' ) ) {
            wp_send_json_error( array( 'message' => __( 'Schedule time must be in the future.', 'ai-ultimate-website-booster' ) ) );
        }

        foreach ( $posts as $post ) {
            $title = sanitize_text_field( $post['title'] ?? '' );
            $content = wp_kses_post( $post['content'] ?? '' );
            $keyword = sanitize_text_field( $post['keyword'] ?? $title );
            $featured_id = absint( $post['featured_id'] ?? 0 );

            if ( empty( $title ) || empty( $content ) ) {
                continue;
            }

            $post_id = wp_insert_post( array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => ( 'schedule' === $status ) ? 'future' : $status,
                'post_type'    => 'post',
                'post_date'    => ( 'schedule' === $status && $schedule_date ) ? $schedule_date : current_time( 'mysql' ),
                'post_date_gmt' => ( 'schedule' === $status && $schedule_date ) ? get_gmt_from_date( $schedule_date ) : get_gmt_from_date( current_time( 'mysql' ) ),
            ) );

            if ( is_wp_error( $post_id ) ) {
                continue;
            }

            $featured_message = '';
            if ( $featured_id ) {
                set_post_thumbnail( $post_id, $featured_id );
                $image_success++;
            } elseif ( $auto_image ) {
                $featured = AIWB_AI::generate_featured_image( $keyword );
                if ( ! empty( $featured['id'] ) ) {
                    set_post_thumbnail( $post_id, (int) $featured['id'] );
                    $featured_id = (int) $featured['id'];
                    $image_success++;
                } else {
                    $featured_message = $featured['message'] ?? __( 'Featured image not generated.', 'ai-ultimate-website-booster' );
                    $image_failed++;
                }
            } else {
                $image_failed++;
                $featured_message = __( 'Featured image not selected.', 'ai-ultimate-website-booster' );
            }

            $created[] = array(
                'id' => $post_id,
                'title' => $title,
                'featured_id' => $featured_id,
                'featured_message' => $featured_message,
            );
        }

        self::log_action( 'bulk_save', array( 'count' => count( $created ) ) );
        if ( $save_reminder && 'schedule' === $status && $schedule_date ) {
            update_option( 'aiwb_bulk_schedule_reminder', array(
                'date' => $schedule_date,
                'count' => count( $created ),
                'post_ids' => wp_list_pluck( $created, 'id' ),
                'updated' => current_time( 'mysql' ),
            ) );
        }
        wp_send_json_success( array(
            'created' => $created,
            'image_success' => $image_success,
            'image_failed' => $image_failed,
            'schedule_date' => $schedule_date,
        ) );
    }

    public function clear_schedule_reminder() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        delete_option( 'aiwb_bulk_schedule_reminder' );
        wp_send_json_success();
    }

    public function schedule_now() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $reminder = get_option( 'aiwb_bulk_schedule_reminder', array() );
        $post_ids = array_map( 'absint', (array) ( $reminder['post_ids'] ?? array() ) );
        $post_ids = array_filter( $post_ids );
        if ( empty( $post_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No scheduled posts found.', 'ai-ultimate-website-booster' ) ) );
        }
        $published = 0;
        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post ) {
                continue;
            }
            $now = current_time( 'mysql' );
            $updated = wp_update_post( array(
                'ID' => $post_id,
                'post_status' => 'publish',
                'post_date' => $now,
                'post_date_gmt' => get_gmt_from_date( $now ),
            ), true );
            if ( ! is_wp_error( $updated ) ) {
                $published++;
            }
        }
        delete_option( 'aiwb_bulk_schedule_reminder' );
        wp_send_json_success( array( 'published' => $published ) );
    }

    public function preview_update() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $post_id = absint( $_POST['post_id'] ?? 0 );
        $tone = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'professional' ) );
        $preview = AIWB_Content_Updater::preview_update( $post_id, $tone );
        wp_send_json_success( $preview );
    }

    public function publish_update() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $post_id = absint( $_POST['post_id'] ?? 0 );
        $content = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
        if ( ! $post_id || empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post update request.', 'ai-ultimate-website-booster' ) ) );
        }
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-ultimate-website-booster' ) ) );
        }
        AIWB_Content_Updater::store_version( $post_id, 'pre-update', $post->post_content );
        wp_update_post( array(
            'ID' => $post_id,
            'post_content' => $content,
        ) );
        self::log_action( 'publish_update', array( 'post_id' => $post_id ) );
        wp_send_json_success( array( 'updated' => true ) );
    }

    public function version_list() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $post_id = absint( $_POST['post_id'] ?? 0 );
        $versions = AIWB_Content_Updater::get_versions( $post_id );
        wp_send_json_success( array( 'versions' => $versions ) );
    }

    public function version_rollback() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $version_id = absint( $_POST['version_id'] ?? 0 );
        $ok = AIWB_Content_Updater::rollback_version( $version_id );
        if ( ! $ok ) {
            wp_send_json_error( array( 'message' => __( 'Rollback failed.', 'ai-ultimate-website-booster' ) ) );
        }
        wp_send_json_success( array( 'rolled_back' => true ) );
    }

    public function health_large_images() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $limit = absint( $_POST['limit'] ?? 10 );
        $results = AIWB_Health::get_large_images( $limit );
        AIWB_Health::store_health_scan( 'large_images', $results );
        wp_send_json_success( array( 'items' => $results ) );
    }

    public function health_db_size() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $size = AIWB_Health::get_database_size();
        wp_send_json_success( array( 'size_mb' => $size ) );
    }

    public function health_speed_tips() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        wp_send_json_success( array( 'tips' => AIWB_Health::page_speed_tips() ) );
    }

    public function save_popup() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        global $wpdb;
        $this->ensure_popup_table();
        $table = $wpdb->prefix . 'aiwb_popups';
        $popup_id = absint( $_POST['popup_id'] ?? 0 );
        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $popup_type = sanitize_text_field( wp_unslash( $_POST['popup_type'] ?? '' ) );
        $settings = array(
            'template' => sanitize_text_field( wp_unslash( $_POST['template'] ?? 'template_1' ) ),
            'headline' => sanitize_text_field( wp_unslash( $_POST['headline'] ?? '' ) ),
            'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
            'button_text' => sanitize_text_field( wp_unslash( $_POST['button_text'] ?? '' ) ),
            'button_url' => esc_url_raw( wp_unslash( $_POST['button_url'] ?? '' ) ),
        );
        $template_map = array(
            'Template One' => 'template_1',
            'Template Two' => 'template_2',
            'Template Three' => 'template_3',
            'Template One - Clean Modal' => 'template_1',
            'Template Two - Offer Spotlight' => 'template_2',
            'Template Three - Hero Layout' => 'template_3',
        );
        if ( isset( $template_map[ $settings['template'] ] ) ) {
            $settings['template'] = $template_map[ $settings['template'] ];
        }
        if ( ! in_array( $settings['template'], array( 'template_1', 'template_2', 'template_3' ), true ) ) {
            $settings['template'] = 'template_1';
        }
        $set_active = sanitize_text_field( wp_unslash( $_POST['set_active'] ?? '1' ) ) === '1';
        $enabled = sanitize_text_field( wp_unslash( $_POST['enabled'] ?? '1' ) ) === '1';

        $data = array(
            'title' => $title ? $title : __( 'Untitled Popup', 'ai-ultimate-website-booster' ),
            'popup_type' => $popup_type,
            'settings_json' => wp_json_encode( $settings ),
            'updated_at' => current_time( 'mysql' ),
        );

        $result = false;
        if ( $popup_id ) {
            $result = $wpdb->update(
                $table,
                $data,
                array( 'id' => $popup_id ),
                array( '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $result = $wpdb->insert(
                $table,
                $data,
                array( '%s', '%s', '%s', '%s', '%s' )
            );
            $popup_id = (int) $wpdb->insert_id;
        }
        if ( false === $result && ! empty( $wpdb->last_error ) ) {
            wp_send_json_error( array( 'message' => $wpdb->last_error ) );
        }

        if ( $set_active ) {
            $option = get_option( 'aiwb_settings', array() );
            $option['popup_template'] = $settings['template'];
            $option['popup_headline'] = $settings['headline'];
            $option['popup_message'] = $settings['message'];
            $option['popup_button_text'] = $settings['button_text'];
            $option['popup_button_url'] = $settings['button_url'];
            $option['popup_enabled'] = $enabled ? '1' : '0';
            update_option( 'aiwb_settings', $option );
        }

        wp_send_json_success( array( 'saved' => true, 'popup_id' => $popup_id ) );
    }

    public function get_popups() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        global $wpdb;
        $this->ensure_popup_table();
        $table = $wpdb->prefix . 'aiwb_popups';
        $rows = $wpdb->get_results( "SELECT id, title, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT 100" );
        $items = array();
        foreach ( (array) $rows as $row ) {
            $items[] = array(
                'id' => (int) $row->id,
                'title' => $row->title,
                'updated_at' => $row->updated_at,
            );
        }
        $option = get_option( 'aiwb_settings', array() );
        $enabled = isset( $option['popup_enabled'] ) ? $option['popup_enabled'] : '1';
        wp_send_json_success( array( 'items' => $items, 'enabled' => $enabled ) );
    }

    public function get_popup() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $popup_id = absint( $_POST['popup_id'] ?? 0 );
        if ( ! $popup_id ) {
            wp_send_json_error( array( 'message' => __( 'Popup not found.', 'ai-ultimate-website-booster' ) ) );
        }
        global $wpdb;
        $this->ensure_popup_table();
        $table = $wpdb->prefix . 'aiwb_popups';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $popup_id ) );
        if ( ! $row ) {
            wp_send_json_error( array( 'message' => __( 'Popup not found.', 'ai-ultimate-website-booster' ) ) );
        }
        $settings = json_decode( $row->settings_json, true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $template = $settings['template'] ?? 'template_1';
        $template_map = array(
            'Template One' => 'template_1',
            'Template Two' => 'template_2',
            'Template Three' => 'template_3',
            'Template One - Clean Modal' => 'template_1',
            'Template Two - Offer Spotlight' => 'template_2',
            'Template Three - Hero Layout' => 'template_3',
        );
        if ( isset( $template_map[ $template ] ) ) {
            $template = $template_map[ $template ];
        }
        if ( ! in_array( $template, array( 'template_1', 'template_2', 'template_3' ), true ) ) {
            $template = 'template_1';
        }
        wp_send_json_success( array(
            'id' => (int) $row->id,
            'title' => $row->title,
            'popup_type' => $row->popup_type,
            'template' => $template,
            'headline' => $settings['headline'] ?? '',
            'message' => $settings['message'] ?? '',
            'button_text' => $settings['button_text'] ?? '',
            'button_url' => $settings['button_url'] ?? '',
        ) );
    }

    public function delete_popup() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $popup_id = absint( $_POST['popup_id'] ?? 0 );
        if ( ! $popup_id ) {
            wp_send_json_error( array( 'message' => __( 'Popup not found.', 'ai-ultimate-website-booster' ) ) );
        }
        global $wpdb;
        $this->ensure_popup_table();
        $table = $wpdb->prefix . 'aiwb_popups';
        $wpdb->delete( $table, array( 'id' => $popup_id ), array( '%d' ) );
        wp_send_json_success();
    }

    public function security_scan_all() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $report = self::run_full_security_scan();
        $user_id = get_current_user_id();
        if ( $user_id ) {
            update_user_meta( $user_id, 'aiwb_last_security_scan_at', time() );
        }
        wp_send_json_success( $report );
    }

    public function security_scan_module() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $module = sanitize_text_field( wp_unslash( $_POST['module'] ?? '' ) );
        if ( empty( $module ) ) {
            wp_send_json_error( array( 'message' => __( 'Module is required.', 'ai-ultimate-website-booster' ) ) );
        }

        $report = AIWB_Health::module_scan( $module );
        if ( empty( $report ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid module.', 'ai-ultimate-website-booster' ) ) );
        }
        $report['generated_at'] = current_time( 'mysql' );

        AIWB_Health::store_health_scan( 'module_' . $module, $report );
        $stored = get_option( 'aiwb_last_module_report', array() );
        if ( ! is_array( $stored ) ) {
            $stored = array();
        }
        $stored[ $module ] = $report;
        update_option( 'aiwb_last_module_report', $stored );
        $report['history'] = AIWB_Health::get_scan_history( 'module_' . $module, 6 );
        self::log_action( 'module_security', array( 'module' => $module, 'score' => $report['security']['score'] ?? 0 ) );
        $user_id = get_current_user_id();
        if ( $user_id ) {
            update_user_meta( $user_id, 'aiwb_last_security_scan_at', time() );
        }

        wp_send_json_success( $report );
    }

    public function export_security_csv() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }

        $user_id = get_current_user_id();
        $last_scan = $user_id ? (int) get_user_meta( $user_id, 'aiwb_last_security_scan_at', true ) : 0;
        if ( ! $last_scan ) {
            wp_die( __( 'No fresh scan found. Run a full security scan first.', 'ai-ultimate-website-booster' ) );
        }

        $report = get_option( 'aiwb_last_security_report', array() );
        if ( empty( $report ) ) {
            wp_die( __( 'No report found. Run a full security scan first.', 'ai-ultimate-website-booster' ) );
        }

        $filename = 'aiwb-security-report-' . gmdate( 'Ymd-His' ) . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $out = fopen( 'php://output', 'w' );
        if ( $out ) {
            fputcsv( $out, array( 'Section', 'Item', 'Status', 'Details' ) );

            if ( ! empty( $report['security']['checks'] ) ) {
                foreach ( $report['security']['checks'] as $item ) {
                    fputcsv( $out, array( 'Security Checks', $item['label'], strtoupper( $item['status'] ), $item['detail'] ) );
                }
            }

            if ( ! empty( $report['broken_links']['broken'] ) ) {
                foreach ( $report['broken_links']['broken'] as $item ) {
                    fputcsv( $out, array( 'Broken Links', $item['url'], $item['status'], $item['post_title'] ) );
                }
            } else {
                fputcsv( $out, array( 'Broken Links', 'None', 'PASS', 'No broken links found.' ) );
            }

            if ( ! empty( $report['unused_assets']['inactive_plugins'] ) ) {
                foreach ( $report['unused_assets']['inactive_plugins'] as $item ) {
                    fputcsv( $out, array( 'Inactive Plugins', $item['name'], 'WARN', $item['path'] ) );
                }
            }
            if ( ! empty( $report['unused_assets']['inactive_themes'] ) ) {
                foreach ( $report['unused_assets']['inactive_themes'] as $item ) {
                    fputcsv( $out, array( 'Inactive Themes', $item['name'], 'WARN', $item['slug'] ) );
                }
            }

            fputcsv( $out, array( 'Database Size', 'Size (MB)', 'INFO', $report['database_size_mb'] ?? 0 ) );

            if ( ! empty( $report['large_images'] ) ) {
                foreach ( $report['large_images'] as $item ) {
                    fputcsv( $out, array( 'Large Images', $item['title'], 'WARN', $item['size_kb'] . ' KB' ) );
                }
            } else {
                fputcsv( $out, array( 'Large Images', 'None', 'PASS', 'No large images detected.' ) );
            }

            if ( ! empty( $report['speed_tips'] ) ) {
                foreach ( $report['speed_tips'] as $tip ) {
                    fputcsv( $out, array( 'Speed Tips', $tip, 'INFO', '' ) );
                }
            }

            fclose( $out );
        }
        exit;
    }

    public function export_security_pdf() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }

        $user_id = get_current_user_id();
        $last_scan = $user_id ? (int) get_user_meta( $user_id, 'aiwb_last_security_scan_at', true ) : 0;
        if ( ! $last_scan ) {
            wp_die( __( 'No fresh scan found. Run a full security scan first.', 'ai-ultimate-website-booster' ) );
        }

        $report = get_option( 'aiwb_last_security_report', array() );
        if ( empty( $report ) ) {
            wp_die( __( 'No report found. Run a full security scan first.', 'ai-ultimate-website-booster' ) );
        }

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );

        $score = isset( $report['security']['score'] ) ? (int) $report['security']['score'] : 0;
        echo '<!doctype html><html><head><meta charset="utf-8">';
        echo '<title>AIWB Security Report</title>';
        echo '<style>body{font-family:Arial,Helvetica,sans-serif;color:#0f172a;margin:24px;}h1{margin:0 0 10px;}h2{margin-top:24px;}table{width:100%;border-collapse:collapse;margin-top:8px;}th,td{border:1px solid #e2e8f0;padding:8px;text-align:left;font-size:13px;}th{background:#f8fafc;} .kpi{display:inline-block;margin-right:16px;font-size:14px;} .muted{color:#64748b;}</style>';
        echo '</head><body>';
        echo '<h1>AIWB Security Report</h1>';
        echo '<p class="muted">Generated at: ' . esc_html( current_time( 'mysql' ) ) . '</p>';
        echo '<div class="kpi"><strong>Score:</strong> ' . esc_html( $score ) . '%</div>';
        echo '<div class="kpi"><strong>DB Size:</strong> ' . esc_html( $report['database_size_mb'] ?? 0 ) . ' MB</div>';

        echo '<h2>Security Checks</h2>';
        echo '<table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';
        if ( ! empty( $report['security']['checks'] ) ) {
            foreach ( $report['security']['checks'] as $item ) {
                echo '<tr><td>' . esc_html( $item['label'] ) . '</td><td>' . esc_html( strtoupper( $item['status'] ) ) . '</td><td>' . esc_html( $item['detail'] ) . '</td></tr>';
            }
        }
        echo '</tbody></table>';

        echo '<h2>Broken Links</h2>';
        echo '<table><thead><tr><th>Status</th><th>URL</th><th>Post</th></tr></thead><tbody>';
        if ( ! empty( $report['broken_links']['broken'] ) ) {
            foreach ( $report['broken_links']['broken'] as $item ) {
                echo '<tr><td>' . esc_html( $item['status'] ) . '</td><td>' . esc_html( $item['url'] ) . '</td><td>' . esc_html( $item['post_title'] ) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="3">No broken links found.</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Unused Assets</h2>';
        echo '<table><thead><tr><th>Type</th><th>Name</th><th>Details</th></tr></thead><tbody>';
        if ( ! empty( $report['unused_assets']['inactive_plugins'] ) ) {
            foreach ( $report['unused_assets']['inactive_plugins'] as $item ) {
                echo '<tr><td>Plugin</td><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['path'] ) . '</td></tr>';
            }
        }
        if ( ! empty( $report['unused_assets']['inactive_themes'] ) ) {
            foreach ( $report['unused_assets']['inactive_themes'] as $item ) {
                echo '<tr><td>Theme</td><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['slug'] ) . '</td></tr>';
            }
        }
        echo '</tbody></table>';

        echo '<h2>Large Images</h2>';
        echo '<table><thead><tr><th>Title</th><th>Size</th></tr></thead><tbody>';
        if ( ! empty( $report['large_images'] ) ) {
            foreach ( $report['large_images'] as $item ) {
                echo '<tr><td>' . esc_html( $item['title'] ) . '</td><td>' . esc_html( $item['size_kb'] ) . ' KB</td></tr>';
            }
        } else {
            echo '<tr><td colspan="2">No large images detected.</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Speed Tips</h2><ul>';
        if ( ! empty( $report['speed_tips'] ) ) {
            foreach ( $report['speed_tips'] as $tip ) {
                echo '<li>' . esc_html( $tip ) . '</li>';
            }
        }
        echo '</ul>';

        echo '<script>window.onload=function(){setTimeout(function(){window.print();},300);};</script>';
        echo '</body></html>';
        exit;
    }

    public function firewall_block_ip() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
        $reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
        $duration = absint( $_POST['duration'] ?? 0 );
        $settings = get_option( 'aiwb_firewall_settings', array( 'auto_unblock' => '1', 'default_duration' => 24 ) );
        if ( $duration <= 0 && ( $settings['auto_unblock'] ?? '1' ) === '1' ) {
            $duration = absint( $settings['default_duration'] ?? 24 );
        }
        $result = AIWB_Health::add_blocked_ip( $ip, $reason, $duration );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        self::log_action( 'firewall_block', array( 'ip' => $ip, 'reason' => $reason ) );
        wp_send_json_success( array( 'message' => __( 'IP blocked.', 'ai-ultimate-website-booster' ) ) );
    }

    public function firewall_unblock_ip() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'ai-ultimate-website-booster' ) ) );
        }
        AIWB_Health::remove_blocked_ip( $id );
        self::log_action( 'firewall_unblock', array( 'id' => $id ) );
        wp_send_json_success( array( 'message' => __( 'IP unblocked.', 'ai-ultimate-website-booster' ) ) );
    }

    public function firewall_save_settings() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $auto = sanitize_text_field( wp_unslash( $_POST['auto_unblock'] ?? '1' ) );
        $duration = absint( $_POST['default_duration'] ?? 24 );
        update_option( 'aiwb_firewall_settings', array(
            'auto_unblock' => $auto === '1' ? '1' : '0',
            'default_duration' => $duration > 0 ? $duration : 24,
        ) );
        wp_send_json_success( array( 'message' => __( 'Firewall rules saved.', 'ai-ultimate-website-booster' ) ) );
    }

    public function allowlist_add() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) );
        $reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );
        $result = AIWB_Health::add_allowlist_ip( $ip, $reason );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        self::log_action( 'allowlist_add', array( 'ip' => $ip, 'reason' => $reason ) );
        wp_send_json_success( array( 'message' => __( 'IP allowlisted.', 'ai-ultimate-website-booster' ) ) );
    }

    public function allowlist_remove() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'ai-ultimate-website-booster' ) ) );
        }
        AIWB_Health::remove_allowlist_ip( $id );
        self::log_action( 'allowlist_remove', array( 'id' => $id ) );
        wp_send_json_success( array( 'message' => __( 'Allowlist entry removed.', 'ai-ultimate-website-booster' ) ) );
    }

    public function clear_logs() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_action_logs';
        $wpdb->query( "TRUNCATE TABLE {$table}" );
        wp_send_json_success( array( 'message' => __( 'Logs cleared.', 'ai-ultimate-website-booster' ) ) );
    }

    public function malware_add_exclusion() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $path = sanitize_text_field( wp_unslash( $_POST['path'] ?? '' ) );
        if ( $path === '' ) {
            wp_send_json_error( array( 'message' => __( 'Path required.', 'ai-ultimate-website-booster' ) ) );
        }
        AIWB_Health::add_malware_exclusion( $path );
        self::log_action( 'malware_exclusion_add', array( 'path' => $path ) );
        wp_send_json_success( array( 'message' => __( 'Exclusion added.', 'ai-ultimate-website-booster' ) ) );
    }

    public function malware_remove_exclusion() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $path = sanitize_text_field( wp_unslash( $_POST['path'] ?? '' ) );
        if ( $path === '' ) {
            wp_send_json_error( array( 'message' => __( 'Path required.', 'ai-ultimate-website-booster' ) ) );
        }
        AIWB_Health::remove_malware_exclusion( $path );
        self::log_action( 'malware_exclusion_remove', array( 'path' => $path ) );
        wp_send_json_success( array( 'message' => __( 'Exclusion removed.', 'ai-ultimate-website-booster' ) ) );
    }

    public function integrity_rebuild() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $baseline = AIWB_Health::build_integrity_baseline();
        self::log_action( 'integrity_baseline', array( 'count' => isset( $baseline['files'] ) ? count( $baseline['files'] ) : 0 ) );
        wp_send_json_success( array( 'message' => __( 'Baseline rebuilt.', 'ai-ultimate-website-booster' ) ) );
    }

    public function integrity_scan() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $results = AIWB_Health::integrity_scan();
        self::log_action( 'integrity_scan', array( 'status' => $results['status'] ?? '' ) );
        wp_send_json_success( $results );
    }

    public function export_module_csv() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }
        $module = sanitize_text_field( wp_unslash( $_GET['module'] ?? '' ) );
        $reports = get_option( 'aiwb_last_module_report', array() );
        $report = $reports[ $module ] ?? array();
        if ( empty( $report ) ) {
            wp_die( __( 'No module report found. Run a module scan first.', 'ai-ultimate-website-booster' ) );
        }
        $filename = 'aiwb-module-' . $module . '-' . gmdate( 'Ymd-His' ) . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        $out = fopen( 'php://output', 'w' );
        if ( $out ) {
            fputcsv( $out, array( 'Section', 'Item', 'Status', 'Details' ) );
            if ( ! empty( $report['security']['checks'] ) ) {
                foreach ( $report['security']['checks'] as $item ) {
                    fputcsv( $out, array( 'Security Checks', $item['label'], strtoupper( $item['status'] ), $item['detail'] ) );
                }
            }
            if ( ! empty( $report['malware_scan'] ) ) {
                fputcsv( $out, array( 'Malware Scan', 'Files Scanned', 'INFO', $report['malware_scan']['files_scanned'] ?? 0 ) );
                fputcsv( $out, array( 'Malware Scan', 'Findings', 'INFO', $report['malware_scan']['findings'] ?? 0 ) );
            }
            fclose( $out );
        }
        exit;
    }

    public function export_module_pdf() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }
        $module = sanitize_text_field( wp_unslash( $_GET['module'] ?? '' ) );
        $reports = get_option( 'aiwb_last_module_report', array() );
        $report = $reports[ $module ] ?? array();
        if ( empty( $report ) ) {
            wp_die( __( 'No module report found. Run a module scan first.', 'ai-ultimate-website-booster' ) );
        }
        $title = esc_html__( 'AIWB Module Security Report', 'ai-ultimate-website-booster' );
        $module_label = esc_html( $report['module_label'] ?? $module );
        $score = esc_html( $report['security']['score'] ?? 0 );
        $html = '<html><head><meta charset="utf-8"><title>' . $title . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;color:#111;margin:24px;}h1{margin:0 0 10px;}table{width:100%;border-collapse:collapse;margin-top:16px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f1f1f1;}</style>';
        $html .= '</head><body>';
        $html .= '<h1>' . $title . '</h1><p><strong>Module:</strong> ' . $module_label . '</p><p><strong>Score:</strong> ' . $score . '%</p>';
        $html .= '<table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';
        if ( ! empty( $report['security']['checks'] ) ) {
            foreach ( $report['security']['checks'] as $item ) {
                $html .= '<tr><td>' . esc_html( $item['label'] ) . '</td><td>' . esc_html( strtoupper( $item['status'] ) ) . '</td><td>' . esc_html( $item['detail'] ) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';
        if ( ! empty( $report['malware_scan'] ) ) {
            $html .= '<h3>Malware Scan</h3><p>Files scanned: ' . esc_html( $report['malware_scan']['files_scanned'] ?? 0 ) . '</p><p>Findings: ' . esc_html( $report['malware_scan']['findings'] ?? 0 ) . '</p>';
        }
        $html .= '<script>window.onload=function(){window.print();};</script></body></html>';
        echo $html;
        exit;
    }

    public function export_security_logs_csv() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }
        $logs = AIWB_Health::recent_actions( 200 );
        $filename = 'aiwb-security-logs-' . gmdate( 'Ymd-His' ) . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        $out = fopen( 'php://output', 'w' );
        if ( $out ) {
            fputcsv( $out, array( 'Time', 'Event', 'IP Address', 'Message' ) );
            foreach ( $logs as $row ) {
                $data = json_decode( $row['action_data'], true );
                $ip = $data['ip'] ?? '-';
                $message = $data['message'] ?? $row['action_name'];
                fputcsv( $out, array( $row['created_at'], $row['action_name'], $ip, $message ) );
            }
            fclose( $out );
        }
        exit;
    }

    public function export_security_logs_pdf() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) );
        }
        $logs = AIWB_Health::recent_actions( 200 );
        $title = esc_html__( 'AIWB Security Logs', 'ai-ultimate-website-booster' );
        $html = '<html><head><meta charset="utf-8"><title>' . $title . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;color:#111;margin:24px;}h1{margin:0 0 10px;}table{width:100%;border-collapse:collapse;margin-top:16px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{background:#f1f1f1;}</style>';
        $html .= '</head><body>';
        $html .= '<h1>' . $title . '</h1>';
        $html .= '<table><thead><tr><th>Time</th><th>Event</th><th>IP</th><th>Message</th></tr></thead><tbody>';
        foreach ( $logs as $row ) {
            $data = json_decode( $row['action_data'], true );
            $ip = $data['ip'] ?? '-';
            $message = $data['message'] ?? $row['action_name'];
            $html .= '<tr><td>' . esc_html( $row['created_at'] ) . '</td><td>' . esc_html( $row['action_name'] ) . '</td><td>' . esc_html( $ip ) . '</td><td>' . esc_html( $message ) . '</td></tr>';
        }
        $html .= '</tbody></table><script>window.onload=function(){window.print();};</script></body></html>';
        echo $html;
        exit;
    }

    public function save_security_schedule() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $enabled = sanitize_text_field( wp_unslash( $_POST['enabled'] ?? '0' ) );
        $frequency = sanitize_text_field( wp_unslash( $_POST['frequency'] ?? 'weekly' ) );
        $hour = absint( $_POST['hour'] ?? 2 );
        if ( ! in_array( $frequency, array( 'daily', 'weekly' ), true ) ) {
            $frequency = 'weekly';
        }
        if ( $hour < 0 || $hour > 23 ) {
            $hour = 2;
        }
        update_option( 'aiwb_security_schedule', array(
            'enabled' => $enabled === '1' ? '1' : '0',
            'frequency' => $frequency,
            'hour' => $hour,
        ) );
        AIWB_Main::maybe_schedule_security_scan( null, get_option( 'aiwb_security_schedule' ) );
        wp_send_json_success( array( 'message' => __( 'Schedule saved.', 'ai-ultimate-website-booster' ) ) );
    }

    public static function run_full_security_scan() {
        $security = AIWB_Health::security_checks();
        $broken = AIWB_Health::scan_broken_links( 8, 20 );
        $assets = AIWB_Health::get_unused_assets();
        $db_size = AIWB_Health::get_database_size();
        $large_images = AIWB_Health::get_large_images( 8, 500 );
        $speed_tips = AIWB_Health::page_speed_tips();
        $malware = AIWB_Health::quick_malware_scan( 200 );
        $history = AIWB_Health::get_scan_history( 'full_security', 6 );
        $module_reports = get_option( 'aiwb_last_module_report', array() );

        $license_present = file_exists( AIWB_PATH . 'readme.txt' ) || file_exists( AIWB_PATH . 'license.txt' );
        $privacy_page = (int) get_option( 'wp_page_for_privacy_policy' );
        $wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
        $compliance = array(
            array(
                'label' => __( 'License/Readme Present', 'ai-ultimate-website-booster' ),
                'status' => $license_present ? 'pass' : 'warn',
                'detail' => $license_present ? __( 'License/readme file detected.', 'ai-ultimate-website-booster' ) : __( 'Add a readme.txt or license.txt file.', 'ai-ultimate-website-booster' ),
            ),
            array(
                'label' => __( 'Privacy Policy Page', 'ai-ultimate-website-booster' ),
                'status' => $privacy_page ? 'pass' : 'warn',
                'detail' => $privacy_page ? __( 'Privacy policy page configured.', 'ai-ultimate-website-booster' ) : __( 'Set a privacy policy page in Settings > Privacy.', 'ai-ultimate-website-booster' ),
            ),
            array(
                'label' => __( 'Debug Mode Disabled', 'ai-ultimate-website-booster' ),
                'status' => $wp_debug ? 'warn' : 'pass',
                'detail' => $wp_debug ? __( 'WP_DEBUG is enabled. Disable for production.', 'ai-ultimate-website-booster' ) : __( 'WP_DEBUG disabled.', 'ai-ultimate-website-booster' ),
            ),
        );

        $report = array(
            'security' => $security,
            'broken_links' => $broken,
            'unused_assets' => $assets,
            'database_size_mb' => $db_size,
            'large_images' => $large_images,
            'speed_tips' => $speed_tips,
            'malware_scan' => $malware,
            'history' => $history,
            'generated_at' => current_time( 'mysql' ),
            'compliance' => $compliance,
            'module_reports' => $module_reports,
        );

        AIWB_Health::store_health_scan( 'full_security', $report );
        update_option( 'aiwb_last_security_report', $report );
        self::log_action( 'full_security', array( 'score' => $security['score'] ?? 0 ) );
        return $report;
    }

    public function generate_image() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $prompt = sanitize_text_field( wp_unslash( $_POST['prompt'] ?? '' ) );
        $result = AIWB_AI::generate_featured_image( $prompt );

        if ( empty( $result['url'] ) || empty( $result['id'] ) ) {
            $message = $result['message'] ?? __( 'Unable to generate image.', 'ai-ultimate-website-booster' );
            wp_send_json_error( array( 'message' => $message ) );
        }

        self::log_action( 'generate_image', array( 'prompt' => $prompt, 'attachment_id' => $result['id'] ) );
        wp_send_json_success( $result );
    }

    public function image_search() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $query = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
        $provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'pixabay' ) );
        $page = absint( $_POST['page'] ?? 1 );
        $per_page = absint( $_POST['per_page'] ?? 12 );

        if ( empty( $query ) ) {
            wp_send_json_error( array( 'message' => __( 'Search query required.', 'ai-ultimate-website-booster' ) ) );
        }

        $results = AIWB_AI::search_images( $query, $provider, $page, $per_page );
        if ( isset( $results['message'] ) ) {
            wp_send_json_error( array( 'message' => $results['message'] ) );
        }
        wp_send_json_success( $results );
    }

    public function image_attach() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $meta = array(
            'source' => sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ),
            'author' => sanitize_text_field( wp_unslash( $_POST['author'] ?? '' ) ),
            'page_url' => esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) ),
        );

        if ( empty( $url ) ) {
            wp_send_json_error( array( 'message' => __( 'Image URL missing.', 'ai-ultimate-website-booster' ) ) );
        }

        $result = AIWB_AI::sideload_image( $url, $title ? $title : __( 'Featured Image', 'ai-ultimate-website-booster' ), $meta );
        if ( isset( $result['message'] ) ) {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
        wp_send_json_success( $result );
    }

    public function create_category() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => __( 'Category name required.', 'ai-ultimate-website-booster' ) ) );
        }
        $term = wp_insert_term( $name, 'category' );
        if ( is_wp_error( $term ) ) {
            wp_send_json_error( array( 'message' => $term->get_error_message() ) );
        }
        wp_send_json_success( array( 'id' => $term['term_id'], 'name' => $name ) );
    }

    public function get_categories() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $terms = get_terms( array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        ) );
        if ( is_wp_error( $terms ) ) {
            wp_send_json_error( array( 'message' => $terms->get_error_message() ) );
        }
        $items = array();
        foreach ( $terms as $term ) {
            $items[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
            );
        }
        wp_send_json_success( array( 'items' => $items ) );
    }

    public function dashboard_data() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'aiwb_action_logs';
        $popup_table = $wpdb->prefix . 'aiwb_popups';

        $settings = wp_parse_args( get_option( 'aiwb_settings', array() ), array(
            'api_key' => '',
            'image_provider' => '',
            'pexels_api_key' => '',
            'pixabay_api_key' => '',
            'automation_enabled' => '0',
            'popup_enabled' => '1',
        ) );
        $api_ready = ! empty( $settings['api_key'] );
        $image_ready = false;
        if ( 'pexels' === ( $settings['image_provider'] ?? '' ) && ! empty( $settings['pexels_api_key'] ) ) {
            $image_ready = true;
        }
        if ( 'pixabay' === ( $settings['image_provider'] ?? '' ) && ! empty( $settings['pixabay_api_key'] ) ) {
            $image_ready = true;
        }
        $automation_on = ! empty( $settings['automation_enabled'] );
        $popup_on = ! empty( $settings['popup_enabled'] );

        $security_report = get_option( 'aiwb_last_security_report', array() );
        $security_score = (int) ( $security_report['security']['score'] ?? 0 );
        if ( $security_score >= 80 ) {
            $security_state = 'ok';
            $security_status = __( 'Protected', 'ai-ultimate-website-booster' );
        } elseif ( $security_score >= 60 ) {
            $security_state = 'warn';
            $security_status = __( 'Needs Review', 'ai-ultimate-website-booster' );
        } else {
            $security_state = 'warn';
            $security_status = __( 'Critical', 'ai-ultimate-website-booster' );
        }

        $get_last_action = function( $actions ) use ( $wpdb, $log_table ) {
            $actions = array_filter( array_map( 'sanitize_text_field', (array) $actions ) );
            if ( empty( $actions ) ) {
                return '';
            }
            $placeholders = implode( ',', array_fill( 0, count( $actions ), '%s' ) );
            $sql = "SELECT MAX(created_at) FROM {$log_table} WHERE action_name IN ({$placeholders})";
            return (string) $wpdb->get_var( $wpdb->prepare( $sql, $actions ) );
        };

        $ai_posts_generated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('generate_content','create_post','bulk_generate')" );
        $posts_updated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('rewrite_posts','publish_update')" );

        $recent_posts = get_posts( array(
            'posts_per_page' => 10,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $seo_total = 0;
        foreach ( $recent_posts as $post ) {
            $content = wp_strip_all_tags( $post->post_content );
            $title = $post->post_title;
            $word_count = str_word_count( $content );
            $score = 0;
            if ( $word_count >= 600 ) {
                $score += 25;
            } elseif ( $word_count >= 300 ) {
                $score += 15;
            }
            if ( preg_match( '/<h2|<h3/i', $post->post_content ) ) {
                $score += 20;
            }
            $keyword = '';
            $title_words = preg_split( '/\\s+/', strtolower( preg_replace( '/[^a-z0-9\\s]/i', '', $title ) ) );
            foreach ( $title_words as $word ) {
                if ( strlen( $word ) >= 4 ) {
                    $keyword = $word;
                    break;
                }
            }
            if ( $keyword && stripos( $content, $keyword ) !== false ) {
                $score += 20;
            }
            if ( preg_match( '/https?:\\/\\//i', $post->post_content ) ) {
                $score += 15;
            }
            if ( preg_match( '/<img[^>]+alt=[\'"][^\'"]+[\'"]/i', $post->post_content ) ) {
                $score += 20;
            }
            $seo_total += min( 100, $score );
        }
        $avg_seo_score = $recent_posts ? round( $seo_total / count( $recent_posts ) ) : 0;

        $unused = AIWB_Health::get_unused_assets();
        $large_images = AIWB_Health::get_large_images( 5, 500 );
        $health_score = 100;
        $health_score -= count( $unused['inactive_plugins'] ) * 2;
        $health_score -= count( $large_images ) * 2;
        $health_score = max( 50, min( 100, $health_score ) );

        $weeks = array( 'Week 1', 'Week 2', 'Week 3', 'Week 4' );
        $week_ranges = array();
        $generated_counts = array();
        $update_counts = array();
        $range_start = date( 'Y-m-d', strtotime( '-27 days' ) );
        for ( $i = 0; $i < 4; $i++ ) {
            $start = date( 'Y-m-d', strtotime( $range_start . ' +' . ( $i * 7 ) . ' days' ) );
            $end = date( 'Y-m-d', strtotime( $start . ' +6 days' ) );
            $week_ranges[] = date( 'M j', strtotime( $start ) ) . ' - ' . date( 'M j', strtotime( $end ) );
            $generated_counts[] = max( 0, (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('generate_content','create_post','bulk_generate') AND DATE(created_at) BETWEEN %s AND %s",
                    $start,
                    $end
                )
            ) );
            $update_counts[] = max( 0, (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('rewrite_posts','publish_update') AND DATE(created_at) BETWEEN %s AND %s",
                    $start,
                    $end
                )
            ) );
        }
        $docs_views = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$log_table} WHERE action_name = 'open_docs'"
        );

        wp_send_json_success( array(
            'stats' => array(
                'generated' => $ai_posts_generated,
                'updated' => $posts_updated,
                'avg_seo' => $avg_seo_score,
                'health' => $health_score,
                'updates_total' => array_sum( $update_counts ),
            ),
            'modules' => array(
                array(
                    'key' => 'ai_content',
                    'title' => __( 'AI Content', 'ai-ultimate-website-booster' ),
                    'status' => $api_ready ? __( 'Ready', 'ai-ultimate-website-booster' ) : __( 'API Key Missing', 'ai-ultimate-website-booster' ),
                    'state' => $api_ready ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'generate_content', 'create_post' ) ),
                    'metric' => $ai_posts_generated,
                    'metric_label' => __( 'Total Generated', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'ai_blog_ideas',
                    'title' => __( 'AI Blog Ideas', 'ai-ultimate-website-booster' ),
                    'status' => $api_ready ? __( 'Ready', 'ai-ultimate-website-booster' ) : __( 'API Key Missing', 'ai-ultimate-website-booster' ),
                    'state' => $api_ready ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'blog_ideas' ) ),
                    'metric' => '',
                    'metric_label' => '',
                ),
                array(
                    'key' => 'bulk_generator',
                    'title' => __( 'Bulk Post Generator', 'ai-ultimate-website-booster' ),
                    'status' => $api_ready ? __( 'Ready', 'ai-ultimate-website-booster' ) : __( 'API Key Missing', 'ai-ultimate-website-booster' ),
                    'state' => $api_ready ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'bulk_generate', 'bulk_save' ) ),
                    'metric' => array_sum( $generated_counts ),
                    'metric_label' => __( 'Generated (30d)', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'seo_tools',
                    'title' => __( 'SEO Tools', 'ai-ultimate-website-booster' ),
                    'status' => $api_ready ? __( 'Ready', 'ai-ultimate-website-booster' ) : __( 'API Key Missing', 'ai-ultimate-website-booster' ),
                    'state' => $api_ready ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'save_seo_meta', 'create_seo_draft' ) ),
                    'metric' => $avg_seo_score . '%',
                    'metric_label' => __( 'Avg SEO', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'ai_images',
                    'title' => __( 'AI Images', 'ai-ultimate-website-booster' ),
                    'status' => $image_ready ? __( 'Provider Ready', 'ai-ultimate-website-booster' ) : __( 'Provider Missing', 'ai-ultimate-website-booster' ),
                    'state' => $image_ready ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'generate_image' ) ),
                    'metric' => $image_ready ? __( 'Connected', 'ai-ultimate-website-booster' ) : __( 'Setup Required', 'ai-ultimate-website-booster' ),
                    'metric_label' => __( 'Image Provider', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'popups',
                    'title' => __( 'Popups', 'ai-ultimate-website-booster' ),
                    'status' => $popup_on ? __( 'Enabled', 'ai-ultimate-website-booster' ) : __( 'Disabled', 'ai-ultimate-website-booster' ),
                    'state' => $popup_on ? 'ok' : 'neutral',
                    'last' => $get_last_action( array( 'save_popup', 'delete_popup' ) ),
                    'metric' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$popup_table}" ),
                    'metric_label' => __( 'Saved Popups', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'health',
                    'title' => __( 'Health Scanner', 'ai-ultimate-website-booster' ),
                    'status' => $health_score >= 80 ? __( 'Healthy', 'ai-ultimate-website-booster' ) : __( 'Needs Attention', 'ai-ultimate-website-booster' ),
                    'state' => $health_score >= 80 ? 'ok' : 'warn',
                    'last' => $get_last_action( array( 'scan_broken_links', 'cleanup_database', 'health_large_images', 'health_db_size', 'health_speed_tips' ) ),
                    'metric' => $health_score . '%',
                    'metric_label' => __( 'Health Score', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'automation',
                    'title' => __( 'Automation', 'ai-ultimate-website-booster' ),
                    'status' => $automation_on ? __( 'Enabled', 'ai-ultimate-website-booster' ) : __( 'Disabled', 'ai-ultimate-website-booster' ),
                    'state' => $automation_on ? 'ok' : 'neutral',
                    'last' => $get_last_action( array( 'automation_run' ) ),
                    'metric' => $automation_on ? __( 'On', 'ai-ultimate-website-booster' ) : __( 'Off', 'ai-ultimate-website-booster' ),
                    'metric_label' => __( 'Scheduler', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'security',
                    'title' => __( 'Security Modules', 'ai-ultimate-website-booster' ),
                    'status' => $security_status,
                    'state' => $security_state,
                    'last' => $get_last_action( array( 'full_security', 'module_security', 'firewall_block', 'firewall_unblock', 'integrity_scan' ) ),
                    'metric' => $security_score ? $security_score . '%' : __( 'N/A', 'ai-ultimate-website-booster' ),
                    'metric_label' => __( 'Security Score', 'ai-ultimate-website-booster' ),
                ),
                array(
                    'key' => 'documentation',
                    'title' => __( 'Documentation', 'ai-ultimate-website-booster' ),
                    'status' => $docs_views > 0 ? __( 'In Use', 'ai-ultimate-website-booster' ) : __( 'Available', 'ai-ultimate-website-booster' ),
                    'state' => 'ok',
                    'last' => $get_last_action( array( 'open_docs' ) ),
                    'metric' => $docs_views,
                    'metric_label' => __( 'Views', 'ai-ultimate-website-booster' ),
                ),
            ),
            'weeks' => $weeks,
            'week_ranges' => $week_ranges,
            'generated_counts' => $generated_counts,
            'update_counts' => $update_counts,
        ) );
    }

    public function save_seo_meta() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $meta_title = sanitize_text_field( wp_unslash( $_POST['meta_title'] ?? '' ) );
        $meta_desc = sanitize_textarea_field( wp_unslash( $_POST['meta_desc'] ?? '' ) );
        $focus_keyword = sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ?? '' ) );
        $faq_schema = wp_kses_post( wp_unslash( $_POST['faq_schema'] ?? '' ) );
        $provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'aiwb' ) );

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Please select a post.', 'ai-ultimate-website-booster' ) ) );
        }
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-ultimate-website-booster' ) ) );
        }

        update_post_meta( $post_id, '_aiwb_meta_title', $meta_title );
        update_post_meta( $post_id, '_aiwb_meta_description', $meta_desc );
        update_post_meta( $post_id, '_aiwb_focus_keyword', $focus_keyword );
        update_post_meta( $post_id, '_aiwb_faq_schema', $faq_schema );

        if ( 'yoast' === $provider || 'aiwb' === $provider ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $meta_title );
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
        }
        if ( 'rankmath' === $provider || 'aiwb' === $provider ) {
            update_post_meta( $post_id, 'rank_math_title', $meta_title );
            update_post_meta( $post_id, 'rank_math_description', $meta_desc );
            update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_keyword );
        }

        self::log_action( 'save_seo_meta', array( 'post_id' => $post_id, 'provider' => $provider ) );
        wp_send_json_success( array(
            'saved' => true,
            'meta_title' => $meta_title,
            'meta_desc' => $meta_desc,
            'focus_keyword' => $focus_keyword,
            'faq_schema' => $faq_schema,
        ) );
    }

    public function get_seo_post() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $post_id = absint( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Please select a post.', 'ai-ultimate-website-booster' ) ) );
        }
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-ultimate-website-booster' ) ) );
        }
        $meta_title = get_post_meta( $post_id, '_aiwb_meta_title', true );
        $meta_desc = get_post_meta( $post_id, '_aiwb_meta_description', true );
        $focus_keyword = get_post_meta( $post_id, '_aiwb_focus_keyword', true );
        $faq_schema = get_post_meta( $post_id, '_aiwb_faq_schema', true );

        if ( ! $meta_title ) {
            $meta_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
        }
        if ( ! $meta_desc ) {
            $meta_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
        }
        if ( ! $focus_keyword ) {
            $focus_keyword = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
        }
        if ( ! $meta_title ) {
            $meta_title = get_post_meta( $post_id, 'rank_math_title', true );
        }
        if ( ! $meta_desc ) {
            $meta_desc = get_post_meta( $post_id, 'rank_math_description', true );
        }
        if ( ! $focus_keyword ) {
            $focus_keyword = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
        }

        if ( ! $meta_title ) {
            $meta_title = $post->post_title;
        }
        if ( ! $meta_desc ) {
            $meta_desc = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24, '...' );
        }

        wp_send_json_success( array(
            'title' => $meta_title,
            'description' => $meta_desc,
            'keyword' => $focus_keyword,
            'faq' => $faq_schema,
            'post_title' => $post->post_title,
        ) );
    }

    public function get_posts_list() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }
        $posts = get_posts( array(
            'posts_per_page' => 100,
            'post_type'      => 'post',
            'post_status'    => array( 'publish', 'draft', 'future' ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
        $items = array();
        foreach ( $posts as $post ) {
            $items[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
            );
        }
        wp_send_json_success( array( 'items' => $items ) );
    }

    public function create_seo_draft() {
        check_ajax_referer( 'aiwb_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'ai-ultimate-website-booster' ) ) );
        }

        $meta_title = sanitize_text_field( wp_unslash( $_POST['meta_title'] ?? '' ) );
        $meta_desc = sanitize_textarea_field( wp_unslash( $_POST['meta_desc'] ?? '' ) );
        $focus_keyword = sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ?? '' ) );

        if ( empty( $meta_title ) ) {
            wp_send_json_error( array( 'message' => __( 'Meta title is required.', 'ai-ultimate-website-booster' ) ) );
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $meta_title,
            'post_content' => $meta_desc ? $meta_desc : __( 'SEO draft created from metadata.', 'ai-ultimate-website-booster' ),
            'post_excerpt' => $meta_desc,
            'post_status'  => 'draft',
            'post_type'    => 'post',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Unable to create draft.', 'ai-ultimate-website-booster' ) ) );
        }

        update_post_meta( $post_id, '_aiwb_meta_title', $meta_title );
        update_post_meta( $post_id, '_aiwb_meta_description', $meta_desc );
        update_post_meta( $post_id, '_aiwb_focus_keyword', $focus_keyword );

        self::log_action( 'create_seo_draft', array( 'post_id' => $post_id ) );
        wp_send_json_success( array(
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link( $post_id, '' ),
        ) );
    }

    public static function build_dummy_ai_response( $topic, $tone ) {
        $title = sprintf( __( 'How to Maximize %s With AI', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        $paragraph = sprintf( __( 'Discover practical steps to improve your website and engagement by using AI to create, optimize, and update content about %s.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        $call_to_action = __( 'Use this AI-driven content model today to grow traffic and keep your pages fresh.', 'ai-ultimate-website-booster' );

        if ( 'friendly' === $tone ) {
            $paragraph = sprintf( __( 'Let’s explore fresh ideas around %s, with a warm tone that feels both helpful and easy to read.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        } elseif ( 'urgent' === $tone ) {
            $paragraph = sprintf( __( 'Right now is the perfect moment to act on %s and boost your results before your competition does.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        }

        return sprintf( '<h2>%s</h2><p>%s</p><h3>%s</h3><p>%s</p>', esc_html( $title ), esc_html( $paragraph ), esc_html__( 'Key Benefits', 'ai-ultimate-website-booster' ), esc_html( $call_to_action ) );
    }

    public static function log_action( $action, $data ) {
        global $wpdb;
        self::ensure_action_log_table();
        $table_name = $wpdb->prefix . 'aiwb_action_logs';
        $wpdb->insert(
            $table_name,
            array(
                'action_name' => sanitize_text_field( $action ),
                'action_data' => wp_json_encode( $data ),
            ),
            array( '%s', '%s' )
        );
    }
}

new AIWB_Ajax();
