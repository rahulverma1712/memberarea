<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="aiwb-popup" class="aiwb-popup" style="display:none;">
    <div class="aiwb-popup-content">
        <button type="button" id="aiwb-popup-close" class="aiwb-popup-close">&times;</button>
        <h2><?php esc_html_e( 'Get AI Optimization Tips', 'ai-ultimate-website-booster' ); ?></h2>
        <p><?php esc_html_e( 'Unlock better conversions with a fast site audit and high-value AI suggestions.', 'ai-ultimate-website-booster' ); ?></p>
        <a href="<?php echo esc_url( home_url() ); ?>" class="button button-primary"><?php esc_html_e( 'View Recommendations', 'ai-ultimate-website-booster' ); ?></a>
    </div>
</div>
