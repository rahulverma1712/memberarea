<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Login_Guard {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        add_action('login_form', array($this, 'render_login_fields'));
        add_filter('authenticate', array($this, 'validate_captcha'), 20, 3);
        add_filter('wp_authenticate_user', array($this, 'validate_totp'), 20, 2);
        add_filter('authenticate', array($this, 'check_lockout'), 30, 3);
        add_action('wp_login_failed', array($this, 'handle_login_failed'), 10, 1);
        add_action('wp_login', array($this, 'handle_login_success'), 10, 2);
    }

    private function key_for($ip, $suffix) {
        $hash = wp_hash($ip . '|' . $suffix);
        return 'zss_login_' . $suffix . '_' . $hash;
    }

    public function check_lockout($user, $username, $password) {
        if (!Utils::boolval(Utils::option('enable_login_guard', '1'))) {
            return $user;
        }

        $ip = Utils::get_ip();
        if (!$ip) {
            return $user;
        }

        $blocklist = Utils::option('login_blocklist', '');
        if ($blocklist && Utils::ip_in_list($ip, $blocklist)) {
            Audit_Log::instance()->log(
                'login_blocked',
                sprintf('Login blocked for IP %s.', $ip),
                null,
                $ip
            );
            return new \WP_Error('zss_blocked', __('Login blocked for this IP address.', 'zshield-sentinel'));
        }

        $allowlist = Utils::option('login_allowlist', '');
        if ($allowlist && !Utils::ip_in_list($ip, $allowlist)) {
            Audit_Log::instance()->log(
                'login_blocked',
                sprintf('Login blocked (not allowlisted) for IP %s.', $ip),
                null,
                $ip
            );
            return new \WP_Error('zss_not_allowed', __('Login not allowed from this IP address.', 'zshield-sentinel'));
        }

        $lock_key = $this->key_for($ip, 'lock');
        $locked_until = get_transient($lock_key);
        if ($locked_until) {
            $message = sprintf(
                /* translators: %s: time remaining */
                __('Too many login attempts. Try again in %s minutes.', 'zshield-sentinel'),
                ceil($locked_until / MINUTE_IN_SECONDS)
            );
            return new \WP_Error('zss_locked', $message);
        }

        return $user;
    }

    public function render_login_fields() {
        if (Utils::boolval(Utils::option('enable_login_captcha', '0'))) {
            $this->render_captcha();
        }

        if (Utils::boolval(Utils::option('enable_2fa', '0'))) {
            echo '<p>';
            echo '<label for="zss_totp">' . esc_html__('Authentication Code', 'zshield-sentinel') . '<br />';
            echo '<input type="text" name="zss_totp" id="zss_totp" class="input" value="" size="20" autocomplete="one-time-code" /></label>';
            echo '<span class="description">' . esc_html__('Enter the 6-digit code from your authenticator app if enabled.', 'zshield-sentinel') . '</span>';
            echo '</p>';
        }
    }

    private function render_captcha() {
        $type = Utils::option('captcha_type', 'math');
        if ($type === 'recaptcha') {
            $site_key = Utils::option('recaptcha_site_key', '');
            if ($site_key) {
                echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($site_key) . '"></div>';
                echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
            }
            return;
        }

        $token = wp_generate_password(12, false, false);
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $op = random_int(0, 1) ? '+' : '-';
        $answer = $op === '+' ? ($a + $b) : ($a - $b);
        set_transient('zss_captcha_' . $token, (string) $answer, 10 * MINUTE_IN_SECONDS);

        echo '<p>';
        echo '<label for="zss_captcha">' . esc_html__('Security Question', 'zshield-sentinel') . '<br />';
        echo '<span class="description">' . esc_html(sprintf('%d %s %d = ?', $a, $op, $b)) . '</span></label>';
        echo '<input type="text" name="zss_captcha" id="zss_captcha" class="input" value="" size="20" />';
        echo '<input type="hidden" name="zss_captcha_token" value="' . esc_attr($token) . '" />';
        echo '</p>';
    }

    public function validate_captcha($user, $username, $password) {
        if (!Utils::boolval(Utils::option('enable_login_captcha', '0'))) {
            return $user;
        }

        $type = Utils::option('captcha_type', 'math');
        if ($type === 'recaptcha') {
            $secret = Utils::option('recaptcha_secret_key', '');
            $response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
            if (!$secret) {
                return $user;
            }
            if (!$response) {
                return new \WP_Error('zss_captcha', __('Captcha verification failed.', 'zshield-sentinel'));
            }

            $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                'timeout' => 10,
                'body' => array(
                    'secret' => $secret,
                    'response' => $response,
                    'remoteip' => Utils::get_ip(),
                ),
            ));

            if (is_wp_error($verify)) {
                return new \WP_Error('zss_captcha', __('Captcha verification failed.', 'zshield-sentinel'));
            }

            $data = json_decode(wp_remote_retrieve_body($verify), true);
            if (empty($data['success'])) {
                return new \WP_Error('zss_captcha', __('Captcha verification failed.', 'zshield-sentinel'));
            }

            return $user;
        }

        $token = isset($_POST['zss_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['zss_captcha_token'])) : '';
        $answer = isset($_POST['zss_captcha']) ? sanitize_text_field(wp_unslash($_POST['zss_captcha'])) : '';
        if (!$token) {
            return new \WP_Error('zss_captcha', __('Captcha verification failed.', 'zshield-sentinel'));
        }
        $expected = get_transient('zss_captcha_' . $token);
        delete_transient('zss_captcha_' . $token);
        if ($expected === false || trim((string) $answer) !== (string) $expected) {
            return new \WP_Error('zss_captcha', __('Captcha verification failed.', 'zshield-sentinel'));
        }

        return $user;
    }

    public function validate_totp($user, $password) {
        if (!$user || is_wp_error($user)) {
            return $user;
        }

        if (!Utils::boolval(Utils::option('enable_2fa', '0'))) {
            return $user;
        }

        if (!Two_Factor::instance()->is_enabled($user->ID)) {
            return $user;
        }

        $code = isset($_POST['zss_totp']) ? sanitize_text_field(wp_unslash($_POST['zss_totp'])) : '';
        $secret = Two_Factor::instance()->get_secret($user->ID);
        if (!$code || !Two_Factor::instance()->verify_code($secret, $code)) {
            return new \WP_Error('zss_totp', __('Invalid authentication code.', 'zshield-sentinel'));
        }

        return $user;
    }

    public function handle_login_failed($username) {
        if (!Utils::boolval(Utils::option('enable_login_guard', '1'))) {
            return;
        }

        $ip = Utils::get_ip();
        if (!$ip) {
            return;
        }

        $attempt_key = $this->key_for($ip, 'attempts');
        $attempts = (int) get_transient($attempt_key);
        $attempts++;

        $max_attempts = absint(Utils::option('max_attempts', 5));
        if ($max_attempts < 1) {
            $max_attempts = 5;
        }

        $lockout_minutes = absint(Utils::option('lockout_minutes', 15));
        if ($lockout_minutes < 1) {
            $lockout_minutes = 15;
        }

        $ttl = $lockout_minutes * MINUTE_IN_SECONDS;

        if ($attempts >= $max_attempts) {
            $lock_key = $this->key_for($ip, 'lock');
            set_transient($lock_key, $ttl, $ttl);

            Audit_Log::instance()->log(
                'login_lockout',
                sprintf('Login locked out for IP %s after %d attempts.', $ip, $attempts),
                null,
                $ip
            );

            if (Utils::boolval(Utils::option('email_lockout_alerts', '0'))) {
                $to = Utils::option('alert_email', '');
                if (!$to) {
                    $to = get_option('admin_email');
                }
                $subject = __('ZShield Sentinel: Login lockout detected', 'zshield-sentinel');
                $body = sprintf(
                    "A login lockout was triggered.\n\nIP: %s\nAttempts: %d\nTime: %s\n",
                    $ip,
                    $attempts,
                    Utils::now_mysql()
                );
                wp_mail($to, $subject, $body);
            }
        } else {
            set_transient($attempt_key, $attempts, $ttl);
        }

        Audit_Log::instance()->log(
            'login_failed',
            sprintf('Login failed for user "%s" from IP %s.', $username, $ip),
            null,
            $ip
        );
    }

    public function handle_login_success($user_login, $user) {
        if (!Utils::boolval(Utils::option('enable_login_guard', '1'))) {
            return;
        }

        $ip = Utils::get_ip();
        if (!$ip) {
            return;
        }

        $attempt_key = $this->key_for($ip, 'attempts');
        delete_transient($attempt_key);

        Audit_Log::instance()->log(
            'login_success',
            sprintf('Login success for user "%s" from IP %s.', $user_login, $ip),
            $user->ID,
            $ip
        );
    }
}

