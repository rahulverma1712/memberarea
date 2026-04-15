<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = esc_html( $args['title'] ?? __( 'Don\u2019t Leave Empty-Handed', 'ai-ultimate-website-booster' ) );
$message = esc_html( $args['message'] ?? __( 'Get a free website growth checklist and AI optimization tips.', 'ai-ultimate-website-booster' ) );
$button_text = esc_html( $args['button_text'] ?? __( 'Start Now', 'ai-ultimate-website-booster' ) );
$button_url = esc_url( $args['button_url'] ?? home_url() );
?>
<div id="aiwb-popup" class="aiwb-popup aiwb-popup-template-one" style="display:none;">
    <div class="aiwb-popup-content">
        <button type="button" id="aiwb-popup-close" class="aiwb-popup-close">&times;</button>
        <h2><?php echo $title; ?></h2>
        <p><?php echo $message; ?></p>
        <a href="<?php echo $button_url; ?>" class="aiwb-popup-btn"><?php echo $button_text; ?></a>
    </div>
</div>
