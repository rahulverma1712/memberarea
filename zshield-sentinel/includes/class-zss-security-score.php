<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Security_Score {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function get_score() {
        $score = 100;
        $tips = array();

        $score -= $this->deduct_if('enable_login_guard', 15, __('Enable Login Guard to slow brute-force attempts.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_2fa', 8, __('Enable two-factor authentication for stronger logins.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('enable_login_captcha', 4, __('Enable login captcha to deter bots.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('enable_login_hardening', 4, __('Enable login URL hardening.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('enable_xmlrpc_disable', 8, __('Disable XML-RPC to reduce exposure.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('disable_file_editor', 6, __('Disable the file editor inside WordPress admin.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('hide_wp_version', 4, __('Hide WordPress version output.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_security_headers', 8, __('Enable security headers for extra protection.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('disable_pingbacks', 4, __('Disable pingbacks to block XML-RPC abuse.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('block_author_enum', 5, __('Block author enumeration to prevent user discovery.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_firewall', 6, __('Enable the application firewall for request filtering.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('enable_audit_log', 5, __('Turn on audit logging for visibility.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_file_integrity', 10, __('Enable file integrity monitoring.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_core_integrity', 5, __('Include WordPress core in integrity scans.', 'zshield-sentinel'), $tips, false);
        $score -= $this->deduct_if('enable_scheduled_scan', 5, __('Schedule automatic scans.', 'zshield-sentinel'), $tips, true);
        $score -= $this->deduct_if('enable_malware_scheduled_scan', 5, __('Schedule automatic malware scans.', 'zshield-sentinel'), $tips, false);

        $baseline = get_option('zss_file_integrity_baseline', array());
        if (empty($baseline)) {
            $score -= 7;
            $tips[] = __('Create a clean baseline to start tracking file changes.', 'zshield-sentinel');
        }

        $last_scan = get_option('zss_file_integrity_last_scan', array());
        if (!empty($last_scan) && is_array($last_scan)) {
            $change_count = (isset($last_scan['added']) ? count($last_scan['added']) : 0)
                + (isset($last_scan['modified']) ? count($last_scan['modified']) : 0)
                + (isset($last_scan['removed']) ? count($last_scan['removed']) : 0);
            if ($change_count > 0) {
                $score -= 8;
                $tips[] = __('Review file integrity changes detected in the last scan.', 'zshield-sentinel');
            }
        }

        $malware = get_option('zss_malware_last_scan', array());
        if (!empty($malware) && isset($malware['risk_score'])) {
            $risk_score = absint($malware['risk_score']);
            if ($risk_score >= 60) {
                $score -= 12;
                $tips[] = __('Malware scan flagged high-risk patterns. Review immediately.', 'zshield-sentinel');
            } elseif ($risk_score > 0) {
                $score -= 6;
                $tips[] = __('Malware scan flagged some risky patterns.', 'zshield-sentinel');
            }
        }

        if ($score < 0) {
            $score = 0;
        }

        $grade = $this->grade($score);

        return array(
            'score' => $score,
            'label' => $grade['label'],
            'class' => $grade['class'],
            'tips' => array_slice(array_unique($tips), 0, 5),
        );
    }

    private function deduct_if($option_key, $points, $tip, &$tips, $default_enabled) {
        $enabled = Utils::boolval(Utils::option($option_key, $default_enabled ? '1' : '0'));
        if (!$enabled) {
            $tips[] = $tip;
            return $points;
        }
        return 0;
    }

    private function grade($score) {
        if ($score >= 90) {
            return array('label' => __('Excellent', 'zshield-sentinel'), 'class' => 'zss-score-excellent');
        }
        if ($score >= 75) {
            return array('label' => __('Good', 'zshield-sentinel'), 'class' => 'zss-score-good');
        }
        if ($score >= 55) {
            return array('label' => __('Needs Attention', 'zshield-sentinel'), 'class' => 'zss-score-warn');
        }
        return array('label' => __('Critical', 'zshield-sentinel'), 'class' => 'zss-score-critical');
    }
}
