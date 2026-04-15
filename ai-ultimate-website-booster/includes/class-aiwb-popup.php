<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Popup {

    public function __construct() {
        add_action( 'wp_footer', array( $this, 'print_popup_html' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_popup_assets' ) );
    }

    public function enqueue_popup_assets() {
        if ( is_admin() ) {
            return;
        }

        $settings = get_option( 'aiwb_settings', array() );
        if ( isset( $settings['popup_enabled'] ) && '0' === $settings['popup_enabled'] ) {
            return;
        }

        wp_enqueue_style( 'aiwb-popup-css', AIWB_URL . 'assets/css/popup.css', array(), AIWB_VERSION );
        wp_enqueue_script( 'aiwb-popup-js', AIWB_URL . 'assets/js/popup.js', array( 'jquery' ), AIWB_VERSION, true );
        wp_localize_script( 'aiwb-popup-js', 'aiwbPopupData', array(
            'trigger'    => $settings['popup_trigger'] ?? 'exit_intent',
            'delay'      => absint( $settings['popup_delay'] ?? 5 ),
            'devices'    => sanitize_text_field( $settings['popup_devices'] ?? 'all' ),
            'scroll'     => absint( $settings['popup_scroll_percent'] ?? 35 ),
            'template'   => sanitize_text_field( $settings['popup_template'] ?? 'template_1' ),
            'siteUrl'    => esc_url( home_url() ),
        ) );
    }

    public function print_popup_html() {
        if ( is_admin() ) {
            return;
        }

        $settings = wp_parse_args( get_option( 'aiwb_settings', array() ), array(
            'popup_template'    => 'template_1',
            'popup_headline'    => '',
            'popup_message'     => '',
            'popup_button_text' => '',
            'popup_button_url'  => home_url(),
            'popup_enabled'     => '1',
        ) );
        if ( isset( $settings['popup_enabled'] ) && '0' === $settings['popup_enabled'] ) {
            return;
        }

        $args = array(
            'title'       => $settings['popup_headline'] ?: __( 'Don\u2019t Leave Empty-Handed', 'ai-ultimate-website-booster' ),
            'message'     => $settings['popup_message'] ?: __( 'Get a free website growth checklist and AI optimization tips.', 'ai-ultimate-website-booster' ),
            'button_text' => $settings['popup_button_text'] ?: __( 'Start Now', 'ai-ultimate-website-booster' ),
            'button_url'  => esc_url( $settings['popup_button_url'] ?: home_url() ),
        );

        $template_file = AIWB_PATH . 'templates/popup-template-1.php';
        if ( 'template_2' === $settings['popup_template'] ) {
            $template_file = AIWB_PATH . 'templates/popup-template-2.php';
        } elseif ( 'template_3' === $settings['popup_template'] ) {
            $template_file = AIWB_PATH . 'templates/popup-template-3.php';
        }

        if ( file_exists( $template_file ) ) {
            include $template_file;
        }
    }
}

new AIWB_Popup();
