<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Two_Factor {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function is_enabled($user_id) {
        return get_user_meta($user_id, 'zss_2fa_enabled', true) === '1';
    }

    public function get_secret($user_id) {
        $secret = get_user_meta($user_id, 'zss_2fa_secret', true);
        if (!$secret) {
            $secret = $this->generate_secret();
            update_user_meta($user_id, 'zss_2fa_secret', $secret);
        }
        return $secret;
    }

    public function regenerate_secret($user_id) {
        $secret = $this->generate_secret();
        update_user_meta($user_id, 'zss_2fa_secret', $secret);
        return $secret;
    }

    public function get_provisioning_uri($user) {
        $secret = $this->get_secret($user->ID);
        $issuer = rawurlencode('ZShield Sentinel');
        $label = rawurlencode('ZShield Sentinel:' . $user->user_login);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    public function verify_code($secret, $code, $window = 1) {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $time_slice = floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            $calc = $this->totp($secret, $time_slice + $i);
            if (hash_equals($calc, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generate_secret($length = 16) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    private function totp($secret, $time_slice) {
        $key = $this->base32_decode($secret);
        if ($key === false) {
            return '';
        }

        $time = pack('N*', 0) . pack('N*', $time_slice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = substr($hash, $offset, 4);
        $value = unpack('N', $truncated);
        $value = $value[1] & 0x7fffffff;
        $mod = $value % 1000000;
        return str_pad((string) $mod, 6, '0', STR_PAD_LEFT);
    }

    private function base32_decode($b32) {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        if ($b32 === '') {
            return false;
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos($alphabet, $b32[$i]);
            if ($val === false) {
                return false;
            }
            $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        for ($i = 0; $i + 8 <= strlen($binary); $i += 8) {
            $bytes .= chr(bindec(substr($binary, $i, 8)));
        }

        return $bytes;
    }
}
