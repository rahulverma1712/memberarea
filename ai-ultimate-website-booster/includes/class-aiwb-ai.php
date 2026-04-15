<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_AI {

    public static function generate_content( $topic, $tone, $length = 'medium', $keyword = '', $language = 'English' ) {
        $settings = get_option( 'aiwb_settings', array() );
        $api_key  = trim( $settings['api_key'] ?? '' );
        $provider = sanitize_text_field( $settings['api_provider'] ?? 'openai' );

        if ( empty( $api_key ) || 'openai' !== $provider ) {
            return self::build_dummy_ai_response( $topic, $tone );
        }

        $length_hint = '1000';
        if ( 'short' === $length ) {
            $length_hint = '600';
        } elseif ( 'long' === $length ) {
            $length_hint = '1600';
        }
        $keyword_hint = $keyword ? "Focus keyword: {$keyword}. " : '';
        $prompt = sprintf(
            "Write a full blog post in HTML about '%s' with a %s tone. Language: %s. %sTarget length: %s words. Include: H2 introduction, 3 H3 sections with short paragraphs, a bullet list of benefits, and a CTA paragraph at the end.",
            $topic,
            $tone,
            $language,
            $keyword_hint,
            $length_hint
        );

        $result = self::call_ai( $prompt, $api_key, $settings );
        return $result ? wp_kses_post( $result ) : self::build_dummy_ai_response( $topic, $tone );
    }

    public static function generate_featured_image( $prompt = '' ) {
        $uploads = wp_upload_dir();
        if ( empty( $uploads['path'] ) || empty( $uploads['url'] ) ) {
            return array( 'message' => __( 'Upload directory not available.', 'ai-ultimate-website-booster' ) );
        }

        if ( ! wp_mkdir_p( $uploads['path'] ) ) {
            return array( 'message' => __( 'Unable to create upload directory.', 'ai-ultimate-website-booster' ) );
        }

        $title = $prompt ? $prompt : __( 'AI Featured Image', 'ai-ultimate-website-booster' );
        $title = trim( preg_replace( '/\\s+/', ' ', $title ) );
        $title = mb_substr( $title, 0, 60 );
        $settings = get_option( 'aiwb_settings', array() );
        $provider = sanitize_text_field( $settings['image_provider'] ?? 'pixabay' );
        $pexels_key = trim( $settings['pexels_api_key'] ?? '' );
        $pixabay_key = trim( $settings['pixabay_api_key'] ?? '' );

        $image_source = array();
        if ( 'pexels' === $provider ) {
            if ( empty( $pexels_key ) ) {
                return array( 'message' => __( 'Please add a Pexels API key in Settings.', 'ai-ultimate-website-booster' ) );
            }
            $image_source = self::fetch_pexels_image( $title, $pexels_key );
        } else {
            if ( empty( $pixabay_key ) ) {
                return array( 'message' => __( 'Please add a Pixabay API key in Settings.', 'ai-ultimate-website-booster' ) );
            }
            $image_source = self::fetch_pixabay_image( $title, $pixabay_key );
        }

        if ( empty( $image_source['url'] ) ) {
            return array( 'message' => $image_source['message'] ?? __( 'No image found for this prompt.', 'ai-ultimate-website-booster' ) );
        }

        $sideload = self::sideload_image( $image_source['url'], $title, $image_source );
        if ( ! empty( $sideload['message'] ) ) {
            return array( 'message' => $sideload['message'] );
        }
        return $sideload;
    }

    public static function search_images( $query, $provider = 'pixabay', $page = 1, $per_page = 12 ) {
        $settings = get_option( 'aiwb_settings', array() );
        $provider = $provider === 'pexels' ? 'pexels' : 'pixabay';
        if ( 'pexels' === $provider ) {
            $pexels_key = trim( $settings['pexels_api_key'] ?? '' );
            if ( empty( $pexels_key ) ) {
                return array( 'message' => __( 'Please add a Pexels API key in Settings.', 'ai-ultimate-website-booster' ) );
            }
            return self::search_pexels_images( $query, $pexels_key, $page, $per_page );
        }
        $pixabay_key = trim( $settings['pixabay_api_key'] ?? '' );
        if ( empty( $pixabay_key ) ) {
            return array( 'message' => __( 'Please add a Pixabay API key in Settings.', 'ai-ultimate-website-booster' ) );
        }
        return self::search_pixabay_images( $query, $pixabay_key, $page, $per_page );
    }

    private static function fetch_pexels_image( $query, $api_key ) {
        $url = 'https://api.pexels.com/v1/search?query=' . rawurlencode( $query ) . '&per_page=1&orientation=landscape';
        $response = wp_remote_get( $url, array(
            'headers' => array( 'Authorization' => $api_key ),
            'timeout' => 30,
        ) );
        if ( is_wp_error( $response ) ) {
            return array( 'message' => $response->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['photos'][0] ) ) {
            return array( 'message' => __( 'No Pexels image found.', 'ai-ultimate-website-booster' ) );
        }
        $photo = $data['photos'][0];
        $src = $photo['src']['large2x'] ?? $photo['src']['large'] ?? '';
        return array(
            'url' => $src,
            'source' => 'pexels',
            'author' => $photo['photographer'] ?? '',
            'page_url' => $photo['url'] ?? '',
        );
    }

    private static function fetch_pixabay_image( $query, $api_key ) {
        $url = 'https://pixabay.com/api/?key=' . rawurlencode( $api_key ) .
            '&q=' . rawurlencode( $query ) . '&image_type=photo&orientation=horizontal&safesearch=true&per_page=3';
        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );
        if ( is_wp_error( $response ) ) {
            return array( 'message' => $response->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['hits'][0] ) ) {
            return array( 'message' => __( 'No Pixabay image found.', 'ai-ultimate-website-booster' ) );
        }
        $hit = $data['hits'][0];
        $src = $hit['largeImageURL'] ?? $hit['webformatURL'] ?? '';
        return array(
            'url' => $src,
            'source' => 'pixabay',
            'author' => $hit['user'] ?? '',
            'page_url' => $hit['pageURL'] ?? '',
        );
    }

    public static function sideload_image( $url, $title, $meta = array() ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $url, 60 );
        if ( is_wp_error( $tmp ) ) {
            return array( 'message' => $tmp->get_error_message() );
        }

        $path = wp_parse_url( $url, PHP_URL_PATH );
        $name = $path ? basename( $path ) : 'aiwb-image-' . time() . '.jpg';
        $file = array(
            'name'     => sanitize_file_name( $name ),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload( $file, 0, $title );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return array( 'message' => $attachment_id->get_error_message() );
        }

        update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
        if ( ! empty( $meta['source'] ) ) {
            update_post_meta( $attachment_id, '_aiwb_image_source', sanitize_text_field( $meta['source'] ) );
        }
        if ( ! empty( $meta['author'] ) ) {
            update_post_meta( $attachment_id, '_aiwb_image_author', sanitize_text_field( $meta['author'] ) );
        }
        if ( ! empty( $meta['page_url'] ) ) {
            update_post_meta( $attachment_id, '_aiwb_image_page', esc_url_raw( $meta['page_url'] ) );
        }

        return array(
            'id'  => $attachment_id,
            'url' => wp_get_attachment_url( $attachment_id ),
        );
    }

    private static function search_pexels_images( $query, $api_key, $page, $per_page ) {
        $url = 'https://api.pexels.com/v1/search?query=' . rawurlencode( $query ) .
            '&per_page=' . absint( $per_page ) . '&page=' . absint( $page ) . '&orientation=landscape';
        $response = wp_remote_get( $url, array(
            'headers' => array( 'Authorization' => $api_key ),
            'timeout' => 30,
        ) );
        if ( is_wp_error( $response ) ) {
            return array( 'message' => $response->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $items = array();
        foreach ( ( $data['photos'] ?? array() ) as $photo ) {
            $items[] = array(
                'thumb' => $photo['src']['medium'] ?? $photo['src']['small'] ?? '',
                'full' => $photo['src']['large2x'] ?? $photo['src']['large'] ?? '',
                'source' => 'pexels',
                'author' => $photo['photographer'] ?? '',
                'page_url' => $photo['url'] ?? '',
            );
        }
        return array(
            'items' => $items,
            'page' => $page,
            'provider' => 'pexels',
        );
    }

    private static function search_pixabay_images( $query, $api_key, $page, $per_page ) {
        $url = 'https://pixabay.com/api/?key=' . rawurlencode( $api_key ) .
            '&q=' . rawurlencode( $query ) . '&image_type=photo&orientation=horizontal&safesearch=true' .
            '&per_page=' . absint( $per_page ) . '&page=' . absint( $page );
        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );
        if ( is_wp_error( $response ) ) {
            return array( 'message' => $response->get_error_message() );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $items = array();
        foreach ( ( $data['hits'] ?? array() ) as $hit ) {
            $items[] = array(
                'thumb' => $hit['webformatURL'] ?? $hit['previewURL'] ?? '',
                'full' => $hit['largeImageURL'] ?? $hit['webformatURL'] ?? '',
                'source' => 'pixabay',
                'author' => $hit['user'] ?? '',
                'page_url' => $hit['pageURL'] ?? '',
            );
        }
        return array(
            'items' => $items,
            'page' => $page,
            'provider' => 'pixabay',
        );
    }

    public static function rewrite_content( $content, $tone, $keywords, $links ) {
        $settings = get_option( 'aiwb_settings', array() );
        $api_key  = trim( $settings['api_key'] ?? '' );

        $keywords_list = $keywords ? implode( ', ', $keywords ) : '';
        $links_list = $links ? implode( ', ', $links ) : '';
        $prompt = "Rewrite the following WordPress post in HTML with a {$tone} tone. Improve clarity, structure, and SEO. Include headings, a short bullet list, and a CTA. Suggested keywords: {$keywords_list}. Suggested internal links: {$links_list}. Original content:\n\n" . wp_strip_all_tags( $content );

        if ( empty( $api_key ) ) {
            return wp_kses_post( $content ) . "\n\n" . wp_kses_post( self::dummy_update_block( $keywords, $links ) );
        }

        $result = self::call_ai( $prompt, $api_key, $settings );
        return $result ? wp_kses_post( $result ) : wp_kses_post( $content ) . "\n\n" . wp_kses_post( self::dummy_update_block( $keywords, $links ) );
    }

    public static function suggest_keywords( $post ) {
        $title = strtolower( $post->post_title );
        $words = preg_split( '/\\s+/', preg_replace( '/[^a-z0-9\\s]/i', '', $title ) );
        $keywords = array();
        foreach ( $words as $word ) {
            if ( strlen( $word ) >= 5 ) {
                $keywords[] = $word;
            }
        }
        $keywords = array_unique( $keywords );
        return array_slice( $keywords, 0, 5 );
    }

    public static function suggest_internal_links( $post_id ) {
        $posts = get_posts( array(
            'posts_per_page' => 3,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'post__not_in'   => array( $post_id ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $links = array();
        foreach ( $posts as $post ) {
            $links[] = get_permalink( $post->ID );
        }
        return $links;
    }

    public static function generate_alt_text( $attachment ) {
        $filename = get_post_meta( $attachment->ID, '_wp_attached_file', true );
        $base = $filename ? basename( $filename ) : $attachment->post_title;
        $base = preg_replace( '/\\.[^.]+$/', '', $base );
        $base = str_replace( array( '-', '_' ), ' ', $base );
        $base = trim( $base );

        if ( $base ) {
            return ucwords( $base );
        }

        return '';
    }

    public static function generate_blog_ideas( $keyword, $count = 20 ) {
        $settings = get_option( 'aiwb_settings', array() );
        $api_key  = trim( $settings['api_key'] ?? '' );
        if ( empty( $api_key ) ) {
            return self::dummy_blog_ideas( $keyword, $count );
        }

        $prompt = sprintf(
            "Generate %d blog post ideas for the keyword '%s'. Return each idea on a new line.",
            $count,
            $keyword
        );
        $result = self::call_ai( $prompt, $api_key, $settings );
        if ( ! $result ) {
            return self::dummy_blog_ideas( $keyword, $count );
        }

        $lines = preg_split( "/\\r\\n|\\r|\\n/", trim( wp_strip_all_tags( $result ) ) );
        $ideas = array();
        foreach ( $lines as $line ) {
            $line = trim( preg_replace( '/^\\d+\\.?\\s*/', '', $line ) );
            if ( $line !== '' ) {
                $ideas[] = $line;
            }
        }
        return array_slice( $ideas, 0, $count );
    }

    public static function generate_seo_meta( $title, $keyword ) {
        $settings = get_option( 'aiwb_settings', array() );
        $api_key  = trim( $settings['api_key'] ?? '' );
        if ( empty( $api_key ) ) {
            return self::dummy_seo_meta( $title, $keyword );
        }

        $prompt = sprintf(
            "Create SEO meta title (<=60 chars), meta description (<=155 chars), and FAQ JSON-LD summary for the topic '%s' with focus keyword '%s'. Return as JSON with keys title, description, faq.",
            $title,
            $keyword
        );
        $result = self::call_ai( $prompt, $api_key, $settings );
        $decoded = json_decode( $result, true );
        if ( is_array( $decoded ) && isset( $decoded['title'], $decoded['description'] ) ) {
            return $decoded;
        }
        return self::dummy_seo_meta( $title, $keyword );
    }

    private static function call_ai( $prompt, $api_key, $settings ) {
        $endpoint = esc_url_raw( $settings['api_endpoint'] ?? 'https://api.openai.com/v1/chat/completions' );
        $model    = sanitize_text_field( $settings['api_model'] ?? 'gpt-4o-mini' );

        $body = array(
            'model'       => $model,
            'temperature' => 0.7,
            'max_tokens'  => 900,
        );

        if ( false !== strpos( $endpoint, '/responses' ) ) {
            $body['input'] = $prompt;
        } else {
            $body['messages'] = array(
                array( 'role' => 'system', 'content' => 'You are a professional WordPress marketing writer.' ),
                array( 'role' => 'user', 'content' => $prompt ),
            );
        }

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data ) ) {
            return '';
        }

        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return trim( $data['choices'][0]['message']['content'] );
        }

        if ( isset( $data['choices'][0]['text'] ) ) {
            return trim( $data['choices'][0]['text'] );
        }

        if ( isset( $data['output'][0]['content'][0]['text'] ) ) {
            return trim( $data['output'][0]['content'][0]['text'] );
        }

        return '';
    }

    public static function build_dummy_ai_response( $topic, $tone ) {
        $title = sprintf( __( 'How to Maximize %s With AI', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        $paragraph = sprintf( __( 'Discover practical steps to improve your website and engagement by using AI to create, optimize, and update content about %s.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        $call_to_action = __( 'Use this AI-driven content model today to grow traffic and keep your pages fresh.', 'ai-ultimate-website-booster' );

        if ( 'friendly' === $tone ) {
            $paragraph = sprintf( __( 'Let\u2019s explore fresh ideas around %s, with a warm tone that feels both helpful and easy to read.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        } elseif ( 'urgent' === $tone ) {
            $paragraph = sprintf( __( 'Right now is the perfect moment to act on %s and boost your results before your competition does.', 'ai-ultimate-website-booster' ), esc_html( $topic ) );
        }

        return sprintf(
            '<h2>%s</h2><p>%s</p><h3>%s</h3><ul><li>%s</li><li>%s</li><li>%s</li></ul><p><strong>%s</strong></p>',
            esc_html( $title ),
            esc_html( $paragraph ),
            esc_html__( 'Key Benefits', 'ai-ultimate-website-booster' ),
            esc_html__( 'Improves on-page SEO and relevance.', 'ai-ultimate-website-booster' ),
            esc_html__( 'Keeps visitors engaged longer.', 'ai-ultimate-website-booster' ),
            esc_html__( 'Creates a clear path to action.', 'ai-ultimate-website-booster' ),
            esc_html( $call_to_action )
        );
    }

    private static function dummy_update_block( $keywords, $links ) {
        $keywords_text = $keywords ? implode( ', ', $keywords ) : __( 'No keywords detected', 'ai-ultimate-website-booster' );
        $links_text = $links ? implode( ', ', $links ) : __( 'No internal links suggested', 'ai-ultimate-website-booster' );

        return '<h3>' . esc_html__( 'AI Update Summary', 'ai-ultimate-website-booster' ) . '</h3>' .
            '<p>' . esc_html__( 'This content was refreshed with improved structure and SEO hints.', 'ai-ultimate-website-booster' ) . '</p>' .
            '<p><strong>' . esc_html__( 'Suggested keywords:', 'ai-ultimate-website-booster' ) . '</strong> ' . esc_html( $keywords_text ) . '</p>' .
            '<p><strong>' . esc_html__( 'Suggested internal links:', 'ai-ultimate-website-booster' ) . '</strong> ' . esc_html( $links_text ) . '</p>';
    }

    private static function dummy_blog_ideas( $keyword, $count ) {
        $ideas = array();
        for ( $i = 1; $i <= $count; $i++ ) {
            $ideas[] = sprintf( __( '%s idea %d', 'ai-ultimate-website-booster' ), ucfirst( $keyword ), $i );
        }
        return $ideas;
    }

    private static function dummy_seo_meta( $title, $keyword ) {
        $title = $title ? $title : __( 'SEO Optimized Title', 'ai-ultimate-website-booster' );
        $keyword = $keyword ? $keyword : __( 'keyword', 'ai-ultimate-website-booster' );
        return array(
            'title' => $title . ' | ' . $keyword,
            'description' => sprintf( __( 'Learn %s and improve your results with practical tips and insights.', 'ai-ultimate-website-booster' ), $keyword ),
            'faq' => array(
                array( 'question' => sprintf( __( 'What is %s?', 'ai-ultimate-website-booster' ), $keyword ), 'answer' => __( 'It is a focused topic to help improve SEO.', 'ai-ultimate-website-booster' ) ),
                array( 'question' => __( 'How to use it?', 'ai-ultimate-website-booster' ), 'answer' => __( 'Add it to titles, headings, and meta description.', 'ai-ultimate-website-booster' ) ),
            ),
        );
    }
}
