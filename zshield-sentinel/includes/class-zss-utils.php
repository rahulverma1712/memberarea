<?php
namespace ZSS;

if (!defined('ABSPATH')) {
    exit;
}

class Utils {
    public static function get_ip() {
        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return $ip;
    }

    public static function now_mysql() {
        return current_time('mysql');
    }

    public static function option($key, $default = null) {
        $options = get_option('zss_settings', array());
        if (!is_array($options)) {
            return $default;
        }
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    public static function update_option($key, $value) {
        $options = get_option('zss_settings', array());
        if (!is_array($options)) {
            $options = array();
        }
        $options[$key] = $value;
        update_option('zss_settings', $options);
    }

    public static function boolval($value) {
        return $value === '1' || $value === 1 || $value === true || $value === 'yes' || $value === 'on';
    }

    public static function required_capability() {
        $role = self::option('access_role', 'administrator');
        $map = array(
            'administrator' => 'manage_options',
            'editor' => 'edit_others_posts',
            'author' => 'publish_posts',
            'contributor' => 'edit_posts',
        );
        return isset($map[$role]) ? $map[$role] : 'manage_options';
    }

    public static function ip_in_list($ip, $raw_list) {
        if (!$ip || !$raw_list) {
            return false;
        }

        $items = preg_split('/[\r\n,]+/', $raw_list);
        if (!$items) {
            return false;
        }

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '' || strpos($item, '#') === 0) {
                continue;
            }

            if (strpos($item, '*') !== false) {
                $pattern = '/^' . str_replace('\*', '.*', preg_quote($item, '/')) . '$/';
                if (preg_match($pattern, $ip)) {
                    return true;
                }
                continue;
            }

            if (strpos($item, '/') !== false) {
                if (self::ip_in_cidr($ip, $item)) {
                    return true;
                }
                continue;
            }

            if (strcasecmp($ip, $item) === 0) {
                return true;
            }
        }

        return false;
    }

    private static function ip_in_cidr($ip, $cidr) {
        $parts = explode('/', $cidr);
        if (count($parts) !== 2) {
            return false;
        }

        $subnet = trim($parts[0]);
        $mask = (int) trim($parts[1]);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($mask < 0 || $mask > 32) {
                return false;
            }

            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            if ($ip_long === false || $subnet_long === false) {
                return false;
            }

            $mask_long = $mask === 0 ? 0 : (-1 << (32 - $mask));
            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }

        return false;
    }
}

