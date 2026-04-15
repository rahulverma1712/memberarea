<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Content_Updater {

    public function __construct() {
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'ensure_alt_text' ), 10, 2 );
    }

    public function ensure_alt_text( $attr, $attachment ) {
        if ( empty( $attr['alt'] ) && ! empty( $attachment->ID ) ) {
            $alt = AIWB_AI::generate_alt_text( $attachment );
            if ( $alt ) {
                $attr['alt'] = $alt;
                update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $alt );
            }
        }
        return $attr;
    }

    public static function scan_old_posts( $age_days = 180, $limit = 10 ) {
        $age_days = max( 30, absint( $age_days ) );
        $limit = max( 1, min( 50, absint( $limit ) ) );
        $date_query = array(
            array(
                'before' => $age_days . ' days ago',
            ),
        );

        $posts = get_posts( array(
            'posts_per_page' => $limit,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'date_query'     => $date_query,
        ) );

        $items = array();
        foreach ( $posts as $post ) {
            $keywords = AIWB_AI::suggest_keywords( $post );
            $links = AIWB_AI::suggest_internal_links( $post->ID );
            $seo_score = $this_score = 0;
            $content = wp_strip_all_tags( $post->post_content );
            $word_count = str_word_count( $content );
            if ( $word_count >= 600 ) {
                $this_score += 25;
            } elseif ( $word_count >= 300 ) {
                $this_score += 15;
            }
            if ( preg_match( '/<h2|<h3/i', $post->post_content ) ) {
                $this_score += 20;
            }
            $keyword = $keywords ? $keywords[0] : '';
            if ( $keyword && stripos( $content, $keyword ) !== false ) {
                $this_score += 20;
            }
            if ( preg_match( '/https?:\\/\\//i', $post->post_content ) ) {
                $this_score += 15;
            }
            if ( preg_match( '/<img[^>]+alt=[\'"][^\'"]+[\'"]/i', $post->post_content ) ) {
                $this_score += 20;
            }
            $seo_score = min( 100, $this_score );
            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => get_the_date( 'Y-m-d', $post->ID ),
                'edit_url' => get_edit_post_link( $post->ID, '' ),
                'keywords' => $keywords,
                'links' => $links,
                'seo_score' => $seo_score,
            );
        }

        return array( 'posts' => $items );
    }

    public static function rewrite_posts( $post_ids, $tone = 'professional' ) {
        $updated = array();
        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post || 'post' !== $post->post_type ) {
                continue;
            }
            self::store_version( $post_id, 'pre-update', $post->post_content );
            $keywords = AIWB_AI::suggest_keywords( $post );
            $links = AIWB_AI::suggest_internal_links( $post_id );
            $new_content = AIWB_AI::rewrite_content( $post->post_content, $tone, $keywords, $links );
            wp_update_post( array(
                'ID' => $post_id,
                'post_content' => $new_content,
            ) );
            $updated[] = array(
                'id' => $post_id,
                'title' => $post->post_title,
            );
        }

        return array(
            'updated' => $updated,
        );
    }

    public static function preview_update( $post_id, $tone = 'professional' ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }
        $keywords = AIWB_AI::suggest_keywords( $post );
        $links = AIWB_AI::suggest_internal_links( $post_id );
        $new_content = AIWB_AI::rewrite_content( $post->post_content, $tone, $keywords, $links );
        return array(
            'original' => $post->post_content,
            'updated' => $new_content,
            'keywords' => $keywords,
            'links' => $links,
        );
    }

    public static function store_version( $post_id, $label, $content ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_content_versions';
        $wpdb->insert(
            $table,
            array(
                'post_id' => absint( $post_id ),
                'version_label' => sanitize_text_field( $label ),
                'content' => wp_kses_post( $content ),
            ),
            array( '%d', '%s', '%s' )
        );
    }

    public static function get_versions( $post_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_content_versions';
        return $wpdb->get_results( $wpdb->prepare( "SELECT id, version_label, created_at FROM {$table} WHERE post_id = %d ORDER BY id DESC LIMIT 10", absint( $post_id ) ), ARRAY_A );
    }

    public static function rollback_version( $version_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'aiwb_content_versions';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $version_id ) ), ARRAY_A );
        if ( ! $row ) {
            return false;
        }
        $post_id = absint( $row['post_id'] );
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }
        self::store_version( $post_id, 'rollback', $post->post_content );
        wp_update_post( array(
            'ID' => $post_id,
            'post_content' => $row['content'],
        ) );
        return true;
    }

    public static function auto_update_posts( $age_days, $limit ) {
        $scan = self::scan_old_posts( $age_days, $limit );
        $ids = wp_list_pluck( $scan['posts'], 'id' );
        if ( empty( $ids ) ) {
            return;
        }
        self::rewrite_posts( $ids, 'professional' );
    }

    public static function generate_missing_alt( $limit = 10 ) {
        $limit = max( 1, min( 50, absint( $limit ) ) );
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'post_status'    => 'inherit',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => '',
                    'compare' => '=',
                ),
            ),
        ) );

        $updated = array();
        foreach ( $attachments as $attachment ) {
            $alt = AIWB_AI::generate_alt_text( $attachment );
            if ( $alt ) {
                update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $alt );
                $updated[] = array(
                    'id' => $attachment->ID,
                    'title' => $attachment->post_title,
                );
            }
        }

        return array( 'updated' => $updated );
    }

    public static function auto_generate_missing_alt( $limit = 10 ) {
        self::generate_missing_alt( $limit );
    }
}

new AIWB_Content_Updater();
