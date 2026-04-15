<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_REST {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            'aiwb/v1',
            '/generate-content',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_generate_content' ),
                'permission_callback' => array( $this, 'permission_check' ),
            )
        );

        register_rest_route(
            'aiwb/v1',
            '/blog-ideas',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_blog_ideas' ),
                'permission_callback' => array( $this, 'permission_check' ),
            )
        );

        register_rest_route(
            'aiwb/v1',
            '/rewrite',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_rewrite' ),
                'permission_callback' => array( $this, 'permission_check' ),
            )
        );

        register_rest_route(
            'aiwb/v1',
            '/seo-meta',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_seo_meta' ),
                'permission_callback' => array( $this, 'permission_check' ),
            )
        );
    }

    public function permission_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    public function rest_generate_content( $request ) {
        $topic = sanitize_text_field( $request->get_param( 'topic' ) );
        $tone  = sanitize_text_field( $request->get_param( 'tone' ) );
        $length = sanitize_text_field( $request->get_param( 'length' ) );
        $keyword = sanitize_text_field( $request->get_param( 'keyword' ) );
        $language = sanitize_text_field( $request->get_param( 'language' ) );

        if ( empty( $topic ) ) {
            return new WP_Error( 'missing_topic', __( 'Topic is required.', 'ai-ultimate-website-booster' ), array( 'status' => 400 ) );
        }

        $result = AIWB_AI::generate_content( $topic, $tone, $length, $keyword, $language );
        return rest_ensure_response( array( 'content' => $result ) );
    }

    public function rest_blog_ideas( $request ) {
        $keyword = sanitize_text_field( $request->get_param( 'keyword' ) );
        $count = absint( $request->get_param( 'count' ) );
        if ( empty( $keyword ) ) {
            return new WP_Error( 'missing_keyword', __( 'Keyword is required.', 'ai-ultimate-website-booster' ), array( 'status' => 400 ) );
        }
        $count = $count ? $count : 20;
        $ideas = AIWB_AI::generate_blog_ideas( $keyword, $count );
        if ( class_exists( 'AIWB_Ajax' ) ) {
            AIWB_Ajax::log_action( 'blog_ideas', array( 'keyword' => $keyword, 'count' => $count ) );
        }
        return rest_ensure_response( array( 'ideas' => $ideas ) );
    }

    public function rest_rewrite( $request ) {
        $content = wp_kses_post( $request->get_param( 'content' ) );
        $tone = sanitize_text_field( $request->get_param( 'tone' ) );
        $keywords = (array) $request->get_param( 'keywords' );
        $links = (array) $request->get_param( 'links' );
        $result = AIWB_AI::rewrite_content( $content, $tone, $keywords, $links );
        return rest_ensure_response( array( 'content' => $result ) );
    }

    public function rest_seo_meta( $request ) {
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $keyword = sanitize_text_field( $request->get_param( 'keyword' ) );
        $result = AIWB_AI::generate_seo_meta( $title, $keyword );
        return rest_ensure_response( $result );
    }
}

new AIWB_REST();
