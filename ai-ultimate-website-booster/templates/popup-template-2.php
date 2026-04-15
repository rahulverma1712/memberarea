<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = esc_html( $args['title'] ?? __( 'Unlock Growth with AI', 'ai-ultimate-website-booster' ) );
$message = esc_html( $args['message'] ?? __( 'Discover high-converting optimization tactics for your website and convert more visitors.', 'ai-ultimate-website-booster' ) );
$button_text = esc_html( $args['button_text'] ?? __( 'Claim Offer', 'ai-ultimate-website-booster' ) );
$button_url = esc_url( $args['button_url'] ?? home_url() );
?>
<div id="aiwb-popup" class="aiwb-popup aiwb-popup-template-two" style="display:none;">
    <div class="aiwb-popup-content aiwb-popup-large">
        <button type="button" id="aiwb-popup-close" class="aiwb-popup-close">&times;</button>
        <div class="aiwb-popup-two-grid">
            <div>
                <span class="aiwb-popup-badge"><?php esc_html_e( 'Premium Lead Magnet', 'ai-ultimate-website-booster' ); ?></span>
                <h2><?php echo $title; ?></h2>
                <p><?php echo $message; ?></p>
            </div>
            <div class="aiwb-popup-action">
                <a href="<?php echo $button_url; ?>" class="aiwb-popup-btn"><?php echo $button_text; ?></a>
            </div>
        </div>
    </div>
</div>
