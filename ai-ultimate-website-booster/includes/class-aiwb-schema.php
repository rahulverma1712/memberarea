<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIWB_Schema {

    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_schema' ) );
    }

    public function output_schema() {
        if ( ! is_singular( 'post' ) ) {
            return;
        }

        $settings = get_option( 'aiwb_settings', array() );
        $schema_type = sanitize_text_field( $settings['schema_type'] ?? 'article' );

        global $post;
        $schema = array();

        if ( 'faq' === $schema_type ) {
            $faq_items = $this->parse_faq_settings( $settings['schema_faq'] ?? '' );
            if ( empty( $faq_items ) ) {
                return;
            }
            $schema = array(
                '@context' => 'https://schema.org',
                '@type'    => 'FAQPage',
                'mainEntity' => $faq_items,
            );
        } elseif ( 'product' === $schema_type ) {
            $product = $this->parse_product_settings( $settings['schema_product'] ?? '' );
            $schema = array(
                '@context' => 'https://schema.org',
                '@type'    => 'Product',
                'name'     => $product['name'] ?: get_the_title( $post->ID ),
                'description' => $product['description'] ?: get_the_excerpt( $post ),
                'sku'      => $product['sku'],
                'brand'    => array(
                    '@type' => 'Brand',
                    'name'  => $product['brand'] ?: get_bloginfo( 'name' ),
                ),
                'offers'   => array(
                    '@type' => 'Offer',
                    'priceCurrency' => $product['currency'],
                    'price' => $product['price'],
                    'availability' => 'https://schema.org/InStock',
                    'url' => get_permalink( $post->ID ),
                ),
            );
        } else {
            $schema = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'Article',
                'headline'        => get_the_title( $post->ID ),
                'datePublished'   => get_the_date( 'c', $post->ID ),
                'dateModified'    => get_the_modified_date( 'c', $post->ID ),
                'author'          => array(
                    '@type' => 'Person',
                    'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                ),
                'publisher'       => array(
                    '@type' => 'Organization',
                    'name'  => get_bloginfo( 'name' ),
                ),
                'description'     => get_the_excerpt( $post ),
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id'   => get_permalink( $post->ID ),
                ),
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
    }

    private function parse_faq_settings( $raw ) {
        $raw = trim( $raw );
        if ( '' === $raw ) {
            return array();
        }

        $decoded = json_decode( $raw, true );
        $items = array();

        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $item ) {
                if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
                    continue;
                }
                $items[] = array(
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ),
                );
            }
            return $items;
        }

        $lines = preg_split( "/\\r\\n|\\r|\\n/", $raw );
        $question = '';
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( stripos( $line, 'Q:' ) === 0 ) {
                $question = trim( substr( $line, 2 ) );
            } elseif ( stripos( $line, 'A:' ) === 0 && $question ) {
                $answer = trim( substr( $line, 2 ) );
                $items[] = array(
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $answer,
                    ),
                );
                $question = '';
            }
        }

        return $items;
    }

    private function parse_product_settings( $raw ) {
        $raw = trim( $raw );
        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            $decoded = array();
        }

        return array(
            'name' => sanitize_text_field( $decoded['name'] ?? '' ),
            'description' => sanitize_text_field( $decoded['description'] ?? '' ),
            'sku' => sanitize_text_field( $decoded['sku'] ?? '' ),
            'brand' => sanitize_text_field( $decoded['brand'] ?? '' ),
            'price' => sanitize_text_field( $decoded['price'] ?? '' ),
            'currency' => sanitize_text_field( $decoded['currency'] ?? 'USD' ),
        );
    }
}

new AIWB_Schema();
