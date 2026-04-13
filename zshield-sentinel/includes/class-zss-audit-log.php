<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Audit_Log {
    private static $instance = null;
    private $table;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'zss_audit_log';
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function log($event_type, $message, $user_id = null, $ip = null) {
        if (!Utils::boolval(Utils::option('enable_audit_log', '1'))) {
            return;
        }

        global $wpdb;
        $data = array(
            'event_type' => sanitize_text_field($event_type),
            'message'    => wp_kses_post($message),
            'user_id'    => $user_id ? absint($user_id) : null,
            'ip_address' => $ip ? sanitize_text_field($ip) : null,
            'created_at' => Utils::now_mysql(),
        );

        $format = array('%s', '%s', '%d', '%s', '%s');
        $wpdb->insert($this->table, $data, $format);
    }

    public function get_logs($limit = 50) {
        global $wpdb;
        $limit = absint($limit);
        if ($limit < 1) {
            $limit = 50;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d",
            $limit
        );
        return $wpdb->get_results($query, ARRAY_A);
    }

    public function prune($days = 30) {
        global $wpdb;
        $days = absint($days);
        if ($days < 1) {
            return;
        }
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table} WHERE created_at < (NOW() - INTERVAL %d DAY)",
                $days
            )
        );
    }
}

