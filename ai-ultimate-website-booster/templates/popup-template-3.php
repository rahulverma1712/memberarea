<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = esc_html( $args['title'] ?? __( 'Save More Time with AI', 'ai-ultimate-website-booster' ) );
$message = esc_html( $args['message'] ?? __( 'Optimize pages, improve SEO, and convert more visitors with our smart popup strategy.', 'ai-ultimate-website-booster' ) );
$button_text = esc_html( $args['button_text'] ?? __( 'View Results', 'ai-ultimate-website-booster' ) );
$button_url = esc_url( $args['button_url'] ?? home_url() );
?>
<div id="aiwb-popup" class="aiwb-popup aiwb-popup-template-three" style="display:none;">
    <div class="aiwb-popup-content aiwb-popup-hero">
        <button type="button" id="aiwb-popup-close" class="aiwb-popup-close">&times;</button>
        <div class="aiwb-popup-hero-inner">
            <h3><?php esc_html_e( 'Exclusive AI Offer', 'ai-ultimate-website-booster' ); ?></h3>
            <h2><?php echo $title; ?></h2>
            <p><?php echo $message; ?></p>
            <a href="<?php echo $button_url; ?>" class="aiwb-popup-btn"><?php echo $button_text; ?></a>
        </div>
    </div>
</div>
