<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class File_Integrity {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function get_scan_paths() {
        $paths = array(
            WP_CONTENT_DIR . '/plugins',
            WP_CONTENT_DIR . '/themes',
        );

        if (Utils::boolval(Utils::option('enable_core_integrity', '0'))) {
            $paths[] = ABSPATH;
        }

        return array_values(array_filter($paths, 'is_dir'));
    }

    public function build_baseline() {
        $files = $this->scan_files(true);
        $baseline = array(
            'created_at' => Utils::now_mysql(),
            'files' => $files,
        );
        update_option('zss_file_integrity_baseline', $baseline);
        Audit_Log::instance()->log('file_baseline', 'File integrity baseline created.');
        return $baseline;
    }

    public function compare_to_baseline() {
        $baseline = get_option('zss_file_integrity_baseline', array());
        $baseline_hashes = array();
        if (isset($baseline['files']) && is_array($baseline['files'])) {
            foreach ($baseline['files'] as $path => $info) {
                if (isset($info['hash'])) {
                    $baseline_hashes[$path] = $info['hash'];
                }
            }
        } elseif (isset($baseline['hashes']) && is_array($baseline['hashes'])) {
            $baseline_hashes = $baseline['hashes'];
        }

        $current_files = $this->scan_files(false);
        $current_hashes = array();
        foreach ($current_files as $path => $info) {
            if (isset($info['hash'])) {
                $current_hashes[$path] = $info['hash'];
            }
        }

        $added = array_diff_key($current_hashes, $baseline_hashes);
        $removed = array_diff_key($baseline_hashes, $current_hashes);

        $modified = array();
        foreach ($current_hashes as $path => $hash) {
            if (isset($baseline_hashes[$path]) && $baseline_hashes[$path] !== $hash) {
                $modified[$path] = $hash;
            }
        }

        $result = array(
            'report_id' => $this->generate_report_id(),
            'scanned_at' => Utils::now_mysql(),
            'added' => array_keys($added),
            'removed' => array_keys($removed),
            'modified' => array_keys($modified),
        );

        update_option('zss_file_integrity_last_scan', $result);
        $this->store_report($result);
        $message = sprintf(
            'File integrity scan completed. Added: %d, Modified: %d, Removed: %d. Report ID: %s',
            count($result['added']),
            count($result['modified']),
            count($result['removed']),
            $result['report_id']
        );
        Audit_Log::instance()->log('file_scan', $message);

        return $result;
    }

    private function generate_report_id() {
        return substr(wp_hash(uniqid('zss', true)), 0, 10);
    }

    private function store_report($report) {
        $reports = get_option('zss_scan_reports', array());
        if (!is_array($reports)) {
            $reports = array();
        }
        array_unshift($reports, $report);
        $reports = array_slice($reports, 0, 20);
        update_option('zss_scan_reports', $reports);
    }

    private function scan_files($with_contents = false) {
        $paths = $this->get_scan_paths();
        $files = array();

        $max_file_bytes = 100 * 1024;
        $max_total_bytes = 2 * 1024 * 1024;
        $total_bytes = 0;

        $allowed_ext = array('php', 'js', 'css', 'html', 'htm', 'json', 'txt', 'md', 'svg');

        foreach ($paths as $path) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file_info) {
                if (!$file_info->isFile()) {
                    continue;
                }

                $ext = strtolower($file_info->getExtension());
                if (!in_array($ext, $allowed_ext, true)) {
                    continue;
                }

                $file_path = $file_info->getPathname();
                if (Utils::boolval(Utils::option('enable_core_integrity', '0'))) {
                    $normalized = str_replace('\\', '/', $file_path);
                    $content_dir = str_replace('\\', '/', WP_CONTENT_DIR);
                    if (strpos($normalized, $content_dir) === 0) {
                        // Skip wp-content when core scan is enabled to avoid double counting.
                        continue;
                    }
                }
                $hash = hash_file('sha256', $file_path);
                $entry = array(
                    'hash' => $hash,
                    'size' => $file_info->getSize(),
                );

                if ($with_contents && $entry['size'] <= $max_file_bytes && $total_bytes + $entry['size'] <= $max_total_bytes) {
                    $content = file_get_contents($file_path);
                    if ($content !== false) {
                        $entry['content'] = $content;
                        $total_bytes += strlen($content);
                    }
                }

                $files[$file_path] = $entry;
            }
        }

        return $files;
    }
}

