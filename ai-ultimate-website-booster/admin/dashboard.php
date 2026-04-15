<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_slug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
$tab_map = array(
    'aiwb-dashboard' => 'dashboard',
    'aiwb-ai-content' => 'ai_content',
    'aiwb-ai-blog-ideas' => 'ai_blog_ideas',
    'aiwb-bulk-post-generator' => 'bulk_post_generator',
    'aiwb-seo-tools' => 'seo_tools',
    'aiwb-popups' => 'popups',
    'aiwb-health-scanner' => 'health_scanner',
    'aiwb-settings' => 'settings',
);
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
if ( ! $active_tab && $page_slug && isset( $tab_map[ $page_slug ] ) ) {
    $active_tab = $tab_map[ $page_slug ];
}
if ( ! $active_tab ) {
    $active_tab = 'dashboard';
}
$defaults = array(
    'popup_trigger'     => 'exit_intent',
    'popup_delay'       => 5,
    'popup_devices'     => 'all',
    'popup_scroll_percent' => 35,
    'api_provider'      => 'openai',
    'api_key'           => '',
    'api_model'         => 'gpt-4o-mini',
    'api_endpoint'      => 'https://api.openai.com/v1/chat/completions',
    'image_provider'    => '',
    'pexels_api_key'    => '',
    'pixabay_api_key'   => '',
    'popup_template'    => 'template_1',
    'popup_headline'    => '',
    'popup_message'     => '',
    'popup_button_text' => '',
    'popup_button_url'  => home_url(),
    'popup_enabled'     => '1',
    'schema_type'       => 'article',
    'schema_faq'        => '',
    'schema_product'    => '',
    'automation_enabled' => '0',
    'automation_frequency' => 'daily',
    'automation_post_age_days' => 180,
    'automation_post_limit' => 3,
    'automation_alt_batch' => 10,
);
$settings = wp_parse_args( get_option( 'aiwb_settings', array() ), $defaults );
if ( function_exists( 'wp_enqueue_editor' ) ) {
    wp_enqueue_editor();
}

global $wpdb;
$log_table = $wpdb->prefix . 'aiwb_action_logs';
$ai_posts_generated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('generate_content','create_post','bulk_generate')" );
$posts_updated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('rewrite_posts','publish_update')" );

function aiwb_calc_seo_score( $post ) {
    if ( ! $post ) {
        return 0;
    }
    $content = wp_strip_all_tags( $post->post_content );
    $title = $post->post_title;
    $word_count = str_word_count( $content );
    $score = 0;
    if ( $word_count >= 600 ) {
        $score += 25;
    } elseif ( $word_count >= 300 ) {
        $score += 15;
    }
    if ( preg_match( '/<h2|<h3/i', $post->post_content ) ) {
        $score += 20;
    }
    $keyword = '';
    $title_words = preg_split( '/\\s+/', strtolower( preg_replace( '/[^a-z0-9\\s]/i', '', $title ) ) );
    foreach ( $title_words as $word ) {
        if ( strlen( $word ) >= 4 ) {
            $keyword = $word;
            break;
        }
    }
    if ( $keyword && stripos( $content, $keyword ) !== false ) {
        $score += 20;
    }
    if ( preg_match( '/https?:\\/\\//i', $post->post_content ) ) {
        $score += 15;
    }
    if ( preg_match( '/<img[^>]+alt=[\'"][^\'"]+[\'"]/i', $post->post_content ) ) {
        $score += 20;
    }
    return min( 100, $score );
}

$recent_posts = get_posts( array(
    'posts_per_page' => 10,
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
) );
$seo_total = 0;
foreach ( $recent_posts as $post ) {
    $seo_total += aiwb_calc_seo_score( $post );
}
$avg_seo_score = $recent_posts ? round( $seo_total / count( $recent_posts ) ) : 0;

$unused = AIWB_Health::get_unused_assets();
$large_images = AIWB_Health::get_large_images( 5, 500 );
$health_score = 100;
$health_score -= count( $unused['inactive_plugins'] ) * 2;
$health_score -= count( $large_images ) * 2;
$health_score = max( 50, min( 100, $health_score ) );
?>
<div class="wrap aiwb-wrap">
    <div id="aiwb-toast" class="aiwb-toast" role="status" aria-live="polite"></div>
    <canvas id="aiwb-star-canvas" class="aiwb-star-canvas" aria-hidden="true"></canvas>
    <div class="aiwb-hero">
        <div class="aiwb-hero-brand">
            <img src="<?php echo esc_url( AIWB_URL . 'assets/images/logo.png' ); ?>" class="aiwb-hero-logo" alt="<?php esc_attr_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?>">
            <div>
                <h1><?php esc_html_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?></h1>
                <p class="aiwb-subtitle"><?php esc_html_e( 'Premium AI toolkit for content, SEO, popups, and health monitoring.', 'ai-ultimate-website-booster' ); ?></p>
            </div>
        </div>
        <div class="aiwb-hero-actions">
            <button class="button button-primary" id="aiwb-hero-generate"><?php esc_html_e( 'Generate New Post', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-hero-health"><?php esc_html_e( 'Run Health Scan', 'ai-ultimate-website-booster' ); ?></button>
        </div>
    </div>

    <div class="aiwb-tab-content">
        <?php if ( 'dashboard' === $active_tab ) : ?>
            <?php $bulk_reminder = get_option( 'aiwb_bulk_schedule_reminder', array() ); ?>
            <div class="aiwb-stat-grid">
                <div class="aiwb-stat-card">
                    <div class="aiwb-stat-icon dashicons dashicons-edit"></div>
                    <div>
                        <p class="aiwb-stat-label"><?php esc_html_e( 'Total AI Posts Generated', 'ai-ultimate-website-booster' ); ?></p>
                        <h3 class="aiwb-stat-value" id="aiwb-stat-generated"><?php echo esc_html( $ai_posts_generated ); ?></h3>
                    </div>
                </div>
                <div class="aiwb-stat-card">
                    <div class="aiwb-stat-icon dashicons dashicons-update"></div>
                    <div>
                        <p class="aiwb-stat-label"><?php esc_html_e( 'Total Posts Updated', 'ai-ultimate-website-booster' ); ?></p>
                        <h3 class="aiwb-stat-value" id="aiwb-stat-updated"><?php echo esc_html( $posts_updated ); ?></h3>
                    </div>
                </div>
                <div class="aiwb-stat-card">
                    <div class="aiwb-stat-icon dashicons dashicons-chart-area"></div>
                    <div>
                        <p class="aiwb-stat-label"><?php esc_html_e( 'Average SEO Score', 'ai-ultimate-website-booster' ); ?></p>
                        <h3 class="aiwb-stat-value" id="aiwb-stat-avg-seo"><?php echo esc_html( $avg_seo_score ); ?>%</h3>
                        <div class="aiwb-progress"><span id="aiwb-progress-avg-seo" style="width:<?php echo esc_attr( $avg_seo_score ); ?>%"></span></div>
                    </div>
                </div>
                <div class="aiwb-stat-card">
                    <div class="aiwb-stat-icon dashicons dashicons-heart"></div>
                    <div>
                        <p class="aiwb-stat-label"><?php esc_html_e( 'Website Health Score', 'ai-ultimate-website-booster' ); ?></p>
                        <h3 class="aiwb-stat-value" id="aiwb-stat-health"><?php echo esc_html( $health_score ); ?>%</h3>
                        <div class="aiwb-progress"><span id="aiwb-progress-health" style="width:<?php echo esc_attr( $health_score ); ?>%"></span></div>
                    </div>
                </div>
            </div>
            <div class="aiwb-card aiwb-card--full aiwb-module-status-card">
                <div class="aiwb-card-header">
                    <h3><?php esc_html_e( 'Module Status', 'ai-ultimate-website-booster' ); ?></h3>
                    <div class="aiwb-chip"><?php esc_html_e( 'Live status', 'ai-ultimate-website-booster' ); ?></div>
                </div>
                <div class="aiwb-module-grid aiwb-module-grid--dashboard" id="aiwb-module-status">
                    <div class="aiwb-module-placeholder"><?php esc_html_e( 'Loading module status…', 'ai-ultimate-website-booster' ); ?></div>
                </div>
            </div>
            <div class="aiwb-card aiwb-card--full">
                <div class="aiwb-card-header">
                    <h3><?php esc_html_e( 'Bulk Schedule Reminder', 'ai-ultimate-website-booster' ); ?></h3>
                    <div class="aiwb-chip"><?php esc_html_e( 'Saved from Bulk Post Generator', 'ai-ultimate-website-booster' ); ?></div>
                </div>
                <?php if ( ! empty( $bulk_reminder['date'] ) ) : ?>
                    <p class="aiwb-muted">
                        <?php
                        printf(
                            esc_html__( 'Next bulk schedule: %1$s (%2$d posts).', 'ai-ultimate-website-booster' ),
                            esc_html( date_i18n( 'M j, Y g:i A', strtotime( $bulk_reminder['date'] ) ) ),
                            absint( $bulk_reminder['count'] ?? 0 )
                        );
                        ?>
                    </p>
                    <div class="aiwb-action-row">
                        <button type="button" class="button" id="aiwb-schedule-now"><?php esc_html_e( 'Publish Now', 'ai-ultimate-website-booster' ); ?></button>
                        <button type="button" class="button" id="aiwb-clear-bulk-reminder"><?php esc_html_e( 'Clear Reminder', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                <?php else : ?>
                    <p class="aiwb-muted"><?php esc_html_e( 'No schedule reminder saved yet. Save one from the Bulk Post Generator.', 'ai-ultimate-website-booster' ); ?></p>
                <?php endif; ?>
            </div>

            <div class="aiwb-layout">
                <div class="aiwb-card aiwb-card--wide">
                    <div class="aiwb-card-header">
                        <h3><?php esc_html_e( 'Insights Overview', 'ai-ultimate-website-booster' ); ?></h3>
                        <div class="aiwb-chip"><?php esc_html_e( 'Last 30 days', 'ai-ultimate-website-booster' ); ?></div>
                    </div>
                    <?php
                    $weeks = array( 'Week 1', 'Week 2', 'Week 3', 'Week 4' );
                    $week_ranges = array();
                    $generated_counts = array();
                    $update_counts = array();
                    $range_start = date( 'Y-m-d', strtotime( '-27 days' ) );
                    for ( $i = 0; $i < 4; $i++ ) {
                        $start = date( 'Y-m-d', strtotime( $range_start . ' +' . ( $i * 7 ) . ' days' ) );
                        $end = date( 'Y-m-d', strtotime( $start . ' +6 days' ) );
                        $week_ranges[] = date( 'M j', strtotime( $start ) ) . '–' . date( 'M j', strtotime( $end ) );
                        $generated_counts[] = max( 0, (int) $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('generate_content','create_post','bulk_generate') AND DATE(created_at) BETWEEN %s AND %s",
                                $start,
                                $end
                            )
                        ) );
                        $update_counts[] = max( 0, (int) $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM {$log_table} WHERE action_name IN ('rewrite_posts','publish_update') AND DATE(created_at) BETWEEN %s AND %s",
                                $start,
                                $end
                            )
                        ) );
                    }
                    $max_gen = max( 1, max( $generated_counts ) );
                    $max_upd = max( 1, max( $update_counts ) );
                    ?>
                    <div class="aiwb-chart-grid">
                        <div class="aiwb-chart">
                            <h4><?php esc_html_e( 'Posts Generated per Week', 'ai-ultimate-website-booster' ); ?></h4>
                            <div class="aiwb-mini-bars" id="aiwb-generated-bars">
                                <?php foreach ( $generated_counts as $i => $val ) : ?>
                                    <div class="aiwb-mini-bar">
                                        <div class="aiwb-mini-value"><?php echo esc_html( $val ); ?></div>
                                        <span style="height: <?php echo esc_attr( ( $val / $max_gen ) * 100 ); ?>%"></span>
                                        <small class="aiwb-mini-label"><?php echo esc_html( $week_ranges[ $i ] ?? $weeks[ $i ] ); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="aiwb-chart">
                            <h4><?php esc_html_e( 'SEO Improvements', 'ai-ultimate-website-booster' ); ?></h4>
                            <div class="aiwb-mini-bars aiwb-mini-bars--accent" id="aiwb-updated-bars">
                                <?php foreach ( $update_counts as $i => $val ) : ?>
                                    <div class="aiwb-mini-bar">
                                        <div class="aiwb-mini-value"><?php echo esc_html( $val ); ?></div>
                                        <span style="height: <?php echo esc_attr( ( $val / $max_upd ) * 100 ); ?>%"></span>
                                        <small class="aiwb-mini-label"><?php echo esc_html( $week_ranges[ $i ] ?? $weeks[ $i ] ); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="aiwb-chart">
                            <h4><?php esc_html_e( 'Content Updates', 'ai-ultimate-website-booster' ); ?></h4>
                            <div class="aiwb-kpi-stack">
                                <div><strong id="aiwb-kpi-updates"><?php echo esc_html( array_sum( $update_counts ) ); ?></strong><span><?php esc_html_e( 'Updates', 'ai-ultimate-website-booster' ); ?></span></div>
                                <div><strong id="aiwb-kpi-total-updated"><?php echo esc_html( $posts_updated ); ?></strong><span><?php esc_html_e( 'Total Updated', 'ai-ultimate-website-booster' ); ?></span></div>
                                <div><strong id="aiwb-kpi-avg-seo"><?php echo esc_html( $avg_seo_score ); ?>%</strong><span><?php esc_html_e( 'Avg SEO', 'ai-ultimate-website-booster' ); ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="aiwb-card aiwb-card--side aiwb-security-snapshot">
                    <div class="aiwb-card-header">
                        <h3><?php esc_html_e( 'Security Modules Snapshot', 'ai-ultimate-website-booster' ); ?></h3>
                        <div class="aiwb-chip"><?php esc_html_e( 'Live status', 'ai-ultimate-website-booster' ); ?></div>
                    </div>
                    <div class="aiwb-security-summary" id="aiwb-security-summary">
                        <p class="aiwb-muted"><?php esc_html_e( 'Run module scans to populate this view.', 'ai-ultimate-website-booster' ); ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ( 'ai_content' === $active_tab ) : ?>
            <div class="aiwb-card aiwb-card--full aiwb-stepper">
                <div class="aiwb-stepper-header">
                    <button class="aiwb-step aiwb-step--active" data-step="1"><?php esc_html_e( 'Step 1: Generate Content', 'ai-ultimate-website-booster' ); ?></button>
                    <button class="aiwb-step" data-step="2"><?php esc_html_e( 'Step 2: Post Settings', 'ai-ultimate-website-booster' ); ?></button>
                </div>
                <div class="aiwb-stepper-body">
                    <div class="aiwb-step-panel aiwb-step-panel--active" data-step="1">
                        <div class="aiwb-card-header">
                            <h2><?php esc_html_e( 'AI Content Generator', 'ai-ultimate-website-booster' ); ?></h2>
                            <div class="aiwb-chip"><?php esc_html_e( 'REST API Enabled', 'ai-ultimate-website-booster' ); ?></div>
                        </div>
                        <p class="description"><?php esc_html_e( 'Generate premium-quality posts with headings, CTA, and SEO keywords. Edit before publishing.', 'ai-ultimate-website-booster' ); ?></p>
                        <form id="aiwb-content-form" method="post">
                            <?php wp_nonce_field( 'aiwb_admin', 'aiwb_nonce' ); ?>
                            <div class="aiwb-form-grid aiwb-form-grid--four">
                                <label>
                                    <span><?php esc_html_e( 'Topic', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="text" id="aiwb-topic" name="topic" class="regular-text" required>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Tone', 'ai-ultimate-website-booster' ); ?></span>
                                    <select id="aiwb-tone" name="tone">
                                        <option value="professional"><?php esc_html_e( 'Professional', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="friendly"><?php esc_html_e( 'Friendly', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="urgent"><?php esc_html_e( 'Urgent', 'ai-ultimate-website-booster' ); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Content Length', 'ai-ultimate-website-booster' ); ?></span>
                                    <select id="aiwb-length" name="length">
                                        <option value="short"><?php esc_html_e( 'Short (600)', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="medium" selected><?php esc_html_e( 'Medium (1000)', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="long"><?php esc_html_e( 'Long (1600)', 'ai-ultimate-website-booster' ); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Focus Keyword', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="text" id="aiwb-keyword" name="keyword" class="regular-text">
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Language', 'ai-ultimate-website-booster' ); ?></span>
                                    <select id="aiwb-language" name="language">
                                        <option value="English"><?php esc_html_e( 'English', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="Hindi"><?php esc_html_e( 'Hindi', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="Spanish"><?php esc_html_e( 'Spanish', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="French"><?php esc_html_e( 'French', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="German"><?php esc_html_e( 'German', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="Arabic"><?php esc_html_e( 'Arabic', 'ai-ultimate-website-booster' ); ?></option>
                                    </select>
                                </label>
                            </div>
                            <div class="aiwb-action-row">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Content', 'ai-ultimate-website-booster' ); ?></button>
                            </div>
                            <div class="aiwb-editor" data-loading-label="<?php esc_attr_e( 'Generating content...', 'ai-ultimate-website-booster' ); ?>">
                                <div class="aiwb-editor-toolbar">
                                    <span><?php esc_html_e( 'Content Editor', 'ai-ultimate-website-booster' ); ?></span>
                                </div>
                                <div class="aiwb-wp-editor">
                                    <?php
                                    wp_editor(
                                        '',
                                        'aiwb-content-result',
                                        array(
                                            'textarea_name' => 'aiwb-content-result',
                                            'textarea_rows' => 14,
                                            'media_buttons' => true,
                                            'teeny'         => false,
                                            'quicktags'     => true,
                                            'content_style' => 'body{background:#120f22 !important;color:#f6f0ff !important;font-family:Manrope,Segoe UI,sans-serif;} p,li,h1,h2,h3,h4,h5,h6{color:#f6f0ff !important;} a{color:#b992ff !important;}',
                                        )
                                    );
                                    ?>
                                </div>
                            </div>
                            <div class="aiwb-copy-row">
                                <button type="button" class="button aiwb-copy-btn" id="aiwb-copy-content"><?php esc_html_e( 'Copy Content', 'ai-ultimate-website-booster' ); ?></button>
                            </div>
                        </form>
                        <div class="aiwb-step-actions">
                            <button class="button button-primary" id="aiwb-go-step-2" disabled><?php esc_html_e( 'Next: Post Settings', 'ai-ultimate-website-booster' ); ?></button>
                        </div>
                    </div>
                    <div class="aiwb-step-panel" data-step="2">
                        <div class="aiwb-card aiwb-card--full">
                            <h3><?php esc_html_e( 'Post Settings', 'ai-ultimate-website-booster' ); ?></h3>
                            <div class="aiwb-form-grid">
                                <label>
                                    <span><?php esc_html_e( 'Post Title', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="text" id="aiwb-post-title">
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Slug', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="text" id="aiwb-post-slug">
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Category', 'ai-ultimate-website-booster' ); ?></span>
                                    <select id="aiwb-post-category"><option value="0"><?php esc_html_e( 'Select category', 'ai-ultimate-website-booster' ); ?></option></select>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Add New Category', 'ai-ultimate-website-booster' ); ?></span>
                                    <div class="aiwb-inline-row">
                                        <input type="text" id="aiwb-new-category" placeholder="<?php esc_attr_e( 'New category name', 'ai-ultimate-website-booster' ); ?>">
                                        <button type="button" class="button" id="aiwb-add-category"><?php esc_html_e( 'Add', 'ai-ultimate-website-booster' ); ?></button>
                                    </div>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Tags', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="text" id="aiwb-post-tags" placeholder="seo, ai, marketing">
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Status', 'ai-ultimate-website-booster' ); ?></span>
                                    <select id="aiwb-post-status">
                                        <option value="draft"><?php esc_html_e( 'Draft', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="publish"><?php esc_html_e( 'Publish', 'ai-ultimate-website-booster' ); ?></option>
                                        <option value="schedule"><?php esc_html_e( 'Schedule', 'ai-ultimate-website-booster' ); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <span><?php esc_html_e( 'Schedule Date', 'ai-ultimate-website-booster' ); ?></span>
                                    <input type="date" id="aiwb-post-schedule">
                                </label>
                            </div>
                            <div class="aiwb-section">
                                <h4><?php esc_html_e( 'AI Featured Image', 'ai-ultimate-website-booster' ); ?></h4>
                                <div class="aiwb-action-row">
                                    <button type="button" class="button button-primary" id="aiwb-generate-image"><?php esc_html_e( 'Generate Image', 'ai-ultimate-website-booster' ); ?></button>
                                    <button type="button" class="button" id="aiwb-regenerate-image"><?php esc_html_e( 'Regenerate Image', 'ai-ultimate-website-booster' ); ?></button>
                                    <button type="button" class="button" id="aiwb-upload-image"><?php esc_html_e( 'Upload Custom Image', 'ai-ultimate-website-booster' ); ?></button>
                                </div>
                                <input type="hidden" id="aiwb-featured-id" value="0">
                                <div class="aiwb-image-preview" id="aiwb-image-preview"><?php esc_html_e( 'Image Preview', 'ai-ultimate-website-booster' ); ?></div>
                            </div>
                            <div class="aiwb-step-actions">
                                <button class="button" id="aiwb-go-step-1"><?php esc_html_e( 'Back', 'ai-ultimate-website-booster' ); ?></button>
                                <button type="button" class="button" id="aiwb-save-draft"><?php esc_html_e( 'Save Draft', 'ai-ultimate-website-booster' ); ?></button>
                                <button type="button" class="button button-primary" id="aiwb-publish-post"><?php esc_html_e( 'Publish Post', 'ai-ultimate-website-booster' ); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ( 'ai_blog_ideas' === $active_tab ) : ?>
            <div class="aiwb-card aiwb-card--full">
                <h2><?php esc_html_e( 'AI Blog Idea Generator', 'ai-ultimate-website-booster' ); ?></h2>
                <p><?php esc_html_e( 'Generate high-impact blog ideas from a seed keyword.', 'ai-ultimate-website-booster' ); ?></p>
                <div class="aiwb-form-grid">
                    <label>
                        <span><?php esc_html_e( 'Keyword', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="text" id="aiwb-idea-keyword" placeholder="wordpress">
                    </label>
                </div>
                <div class="aiwb-action-row">
                    <button class="button button-primary" id="aiwb-generate-ideas"><?php esc_html_e( 'Generate Ideas', 'ai-ultimate-website-booster' ); ?></button>
                    <button class="button" id="aiwb-idea-to-post"><?php esc_html_e( 'Convert Idea to Post', 'ai-ultimate-website-booster' ); ?></button>
                </div>
                <div class="aiwb-result aiwb-ideas-list" id="aiwb-ideas-result"></div>
            </div>
        <?php elseif ( 'bulk_post_generator' === $active_tab ) : ?>
            <div class="aiwb-card aiwb-card--full">
                <h2><?php esc_html_e( 'Bulk Post Generator', 'ai-ultimate-website-booster' ); ?></h2>
                <p><?php esc_html_e( 'Paste multiple keywords and generate posts in bulk.', 'ai-ultimate-website-booster' ); ?></p>
                <div class="aiwb-form-grid">
                    <label class="aiwb-form-wide">
                        <span><?php esc_html_e( 'Keywords (one per line)', 'ai-ultimate-website-booster' ); ?></span>
                        <textarea id="aiwb-bulk-keywords" rows="6" placeholder="wordpress seo"></textarea>
                    </label>
                </div>
                <div class="aiwb-form-grid">
                    <label>
                        <span><?php esc_html_e( 'Number of posts', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="number" id="aiwb-bulk-count" value="5">
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Post status', 'ai-ultimate-website-booster' ); ?></span>
                        <select id="aiwb-bulk-status">
                            <option value="draft"><?php esc_html_e( 'Draft', 'ai-ultimate-website-booster' ); ?></option>
                            <option value="publish"><?php esc_html_e( 'Publish', 'ai-ultimate-website-booster' ); ?></option>
                            <option value="schedule"><?php esc_html_e( 'Schedule', 'ai-ultimate-website-booster' ); ?></option>
                        </select>
                    </label>
                    <label class="aiwb-bulk-schedule-inline">
                        <span><?php esc_html_e( 'Schedule after (days)', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="number" id="aiwb-bulk-schedule-days" min="1" placeholder="2">
                    </label>
                    <label class="aiwb-bulk-schedule-inline">
                        <span><?php esc_html_e( 'Schedule date', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="date" id="aiwb-bulk-schedule-date">
                    </label>
                    <label class="aiwb-bulk-schedule-inline">
                        <span><?php esc_html_e( 'Schedule time', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="time" id="aiwb-bulk-schedule-time" value="09:00">
                    </label>
                </div>
                <div class="aiwb-action-row">
                    <button class="button button-primary" id="aiwb-bulk-generate"><?php esc_html_e( 'Generate Posts', 'ai-ultimate-website-booster' ); ?></button>
                    <button class="button" id="aiwb-bulk-schedule"><?php esc_html_e( 'Generate & Schedule', 'ai-ultimate-website-booster' ); ?></button>
                </div>
                <div class="aiwb-editor aiwb-bulk-editor" data-loading-label="<?php esc_attr_e( 'Generating posts...', 'ai-ultimate-website-booster' ); ?>">
                    <div class="aiwb-editor-toolbar">
                        <span><?php esc_html_e( 'Generated Posts', 'ai-ultimate-website-booster' ); ?></span>
                        <span class="aiwb-muted" id="aiwb-bulk-summary"><?php esc_html_e( 'No drafts yet. Generate posts to edit and save.', 'ai-ultimate-website-booster' ); ?></span>
                        <label class="aiwb-inline-row aiwb-bulk-auto-image">
                            <input type="checkbox" id="aiwb-bulk-auto-image" checked>
                            <span><?php esc_html_e( 'Auto-generate featured images if missing', 'ai-ultimate-website-booster' ); ?></span>
                        </label>
                        <label class="aiwb-inline-row aiwb-bulk-reminder">
                            <input type="checkbox" id="aiwb-bulk-reminder" checked>
                            <span><?php esc_html_e( 'Save schedule reminder to dashboard', 'ai-ultimate-website-booster' ); ?></span>
                        </label>
                    </div>
                    <div class="aiwb-bulk-generated" id="aiwb-bulk-generated"></div>
                    <div class="aiwb-bulk-savebar">
                        <button class="button button-primary" id="aiwb-bulk-save" disabled><?php esc_html_e( 'Save Posts', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                </div>
            </div>
<?php elseif ( 'content_updater' === $active_tab ) : ?>
            <div class="aiwb-card aiwb-card--full">
                <h2><?php esc_html_e( 'Content Updater', 'ai-ultimate-website-booster' ); ?></h2>
                <p><?php esc_html_e( 'Scan older posts and update content with AI-driven improvements.', 'ai-ultimate-website-booster' ); ?></p>
                <div class="aiwb-form-grid">
                    <label>
                        <span><?php esc_html_e( 'Older than (days)', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="number" id="aiwb-updater-age" value="180">
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Posts limit', 'ai-ultimate-website-booster' ); ?></span>
                        <input type="number" id="aiwb-updater-limit" value="20">
                    </label>
                </div>
                <div class="aiwb-action-row">
                    <button class="button button-primary" id="aiwb-updater-scan"><?php esc_html_e( 'Scan Old Content', 'ai-ultimate-website-booster' ); ?></button>
                </div>
                <div class="aiwb-table" id="aiwb-updater-table"></div>
            </div>

            <div class="aiwb-card aiwb-card--full">
                <h3><?php esc_html_e( 'Content Comparison', 'ai-ultimate-website-booster' ); ?></h3>
                <div class="aiwb-compare">
                    <div>
                        <h4><?php esc_html_e( 'Original Content', 'ai-ultimate-website-booster' ); ?></h4>
                        <div class="aiwb-compare-box" id="aiwb-compare-original"><?php esc_html_e( 'Original content preview...', 'ai-ultimate-website-booster' ); ?></div>
                    </div>
                    <div>
                        <h4><?php esc_html_e( 'AI Updated Content', 'ai-ultimate-website-booster' ); ?></h4>
                        <div class="aiwb-compare-box aiwb-compare-box--highlight" id="aiwb-compare-updated"><?php esc_html_e( 'Updated content preview with highlights...', 'ai-ultimate-website-booster' ); ?></div>
                    </div>
                </div>
                <div class="aiwb-action-row">
                    <button class="button button-primary" id="aiwb-accept-update"><?php esc_html_e( 'Accept Change', 'ai-ultimate-website-booster' ); ?></button>
                    <button class="button" id="aiwb-reject-update"><?php esc_html_e( 'Reject Change', 'ai-ultimate-website-booster' ); ?></button>
                    <button class="button" id="aiwb-publish-update"><?php esc_html_e( 'Publish Update', 'ai-ultimate-website-booster' ); ?></button>
                </div>
            </div>

            <div class="aiwb-card aiwb-card--full">
                <h3><?php esc_html_e( 'Version History', 'ai-ultimate-website-booster' ); ?></h3>
                <div class="aiwb-version-list" id="aiwb-version-list"></div>
            </div>
        <?php elseif ( 'seo_tools' === $active_tab ) : ?>
            <div class="aiwb-grid">
                <div class="aiwb-card aiwb-card--full">
                    <h2><?php esc_html_e( 'SEO Tools', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Generate SEO metadata and schema with an actionable checklist.', 'ai-ultimate-website-booster' ); ?></p>
                    <div class="aiwb-form-grid">
                        <label>
                            <span><?php esc_html_e( 'Meta Title', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="text" id="aiwb-meta-title">
                        </label>
                        <label>
                            <span><?php esc_html_e( 'Focus Keyword', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="text" id="aiwb-meta-keyword">
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Meta Description', 'ai-ultimate-website-booster' ); ?></span>
                            <textarea id="aiwb-meta-description" rows="3"></textarea>
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'FAQ Schema (JSON-LD)', 'ai-ultimate-website-booster' ); ?></span>
                            <textarea id="aiwb-seo-faq" rows="4" placeholder='{"@context":"https://schema.org","@type":"FAQPage","mainEntity": []}'></textarea>
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Target Post', 'ai-ultimate-website-booster' ); ?></span>
                            <select id="aiwb-seo-post">
                                <option value=""><?php esc_html_e( 'Select a post...', 'ai-ultimate-website-booster' ); ?></option>
                            </select>
                        </label>
                    </div>
                    <div class="aiwb-action-row">
                        <button class="button button-primary" id="aiwb-generate-seo"><?php esc_html_e( 'Generate SEO', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-generate-faq"><?php esc_html_e( 'Generate FAQ Schema', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-copy-seo"><?php esc_html_e( 'Copy Meta', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-save-seo-meta"><?php esc_html_e( 'Save to Post Meta', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-apply-yoast"><?php esc_html_e( 'Apply to Yoast', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-apply-rankmath"><?php esc_html_e( 'Apply to Rank Math', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button button-primary" id="aiwb-seo-draft"><?php esc_html_e( 'Copy + Save Draft', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                </div>
                <div class="aiwb-card">
                    <h3><?php esc_html_e( 'SEO Score', 'ai-ultimate-website-booster' ); ?></h3>
                    <div class="aiwb-score" id="aiwb-seo-score">0%</div>
                    <div class="aiwb-progress"><span id="aiwb-seo-progress" style="width:0%"></span></div>
                    <ul class="aiwb-checklist" id="aiwb-seo-checklist">
                        <li><?php esc_html_e( 'Keyword in title', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Keyword in description', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Title length 30–60 chars', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Description length 120–160 chars', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Focus keyword set', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </div>
        <?php elseif ( 'popups' === $active_tab ) : ?>
            <div class="aiwb-grid">
                <div class="aiwb-card aiwb-card--full">
                    <h2><?php esc_html_e( 'Popup Builder', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Design popups for lead capture, discounts, exit intent, and announcements.', 'ai-ultimate-website-booster' ); ?></p>
                    <div class="aiwb-form-grid">
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Saved Popups', 'ai-ultimate-website-booster' ); ?></span>
                            <div class="aiwb-inline-row">
                                <select id="aiwb-popup-select">
                                    <option value=""><?php esc_html_e( 'Select a saved popup...', 'ai-ultimate-website-booster' ); ?></option>
                                </select>
                                <button type="button" class="button" id="aiwb-popup-load"><?php esc_html_e( 'Load', 'ai-ultimate-website-booster' ); ?></button>
                                <button type="button" class="button" id="aiwb-popup-new"><?php esc_html_e( 'New', 'ai-ultimate-website-booster' ); ?></button>
                                <button type="button" class="button" id="aiwb-popup-delete"><?php esc_html_e( 'Delete', 'ai-ultimate-website-booster' ); ?></button>
                            </div>
                            <p class="aiwb-muted" id="aiwb-popup-updated"><?php esc_html_e( 'Last updated: —', 'ai-ultimate-website-booster' ); ?></p>
                        </label>
                    </div>
                    <div class="aiwb-form-grid">
                        <label>
                            <span><?php esc_html_e( 'Popup Type', 'ai-ultimate-website-booster' ); ?></span>
                            <select id="aiwb-popup-type">
                                <option value="Lead Capture"><?php esc_html_e( 'Lead Capture', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="Discount Popup"><?php esc_html_e( 'Discount Popup', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="Exit Intent Popup"><?php esc_html_e( 'Exit Intent Popup', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="Announcement Bar"><?php esc_html_e( 'Announcement Bar', 'ai-ultimate-website-booster' ); ?></option>
                            </select>
                        </label>
                        <label>
                            <span><?php esc_html_e( 'Template', 'ai-ultimate-website-booster' ); ?></span>
                            <select id="aiwb-popup-template">
                                <option value="template_1"><?php esc_html_e( 'Template One', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="template_2"><?php esc_html_e( 'Template Two', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="template_3"><?php esc_html_e( 'Template Three', 'ai-ultimate-website-booster' ); ?></option>
                            </select>
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Popup Title', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="text" id="aiwb-popup-title">
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Button Text', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="text" id="aiwb-popup-button-text" placeholder="<?php esc_attr_e( 'Start Now', 'ai-ultimate-website-booster' ); ?>">
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Button URL', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="url" id="aiwb-popup-button-url" placeholder="<?php echo esc_url( home_url() ); ?>">
                        </label>
                        <label class="aiwb-form-wide">
                            <span><?php esc_html_e( 'Popup Content', 'ai-ultimate-website-booster' ); ?></span>
                            <textarea id="aiwb-popup-content" rows="4"></textarea>
                        </label>
                    </div>
                    <div class="aiwb-action-row">
                        <button class="button button-primary" id="aiwb-save-popup"><?php esc_html_e( 'Save Popup', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-preview-popup"><?php esc_html_e( 'Preview Popup', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-popup-dummy"><?php esc_html_e( 'Insert Dummy Content', 'ai-ultimate-website-booster' ); ?></button>
                        <label class="aiwb-inline-row">
                            <input type="checkbox" id="aiwb-popup-set-active" checked>
                            <span><?php esc_html_e( 'Set as active popup', 'ai-ultimate-website-booster' ); ?></span>
                        </label>
                        <label class="aiwb-inline-row">
                            <input type="checkbox" id="aiwb-popup-enabled" checked>
                            <span><?php esc_html_e( 'Enable popup on site', 'ai-ultimate-website-booster' ); ?></span>
                        </label>
                    </div>
                    <div class="aiwb-inline-row aiwb-popup-preview-tabs">
                        <button type="button" class="button aiwb-preview-tab is-active" data-preview="desktop"><?php esc_html_e( 'Desktop', 'ai-ultimate-website-booster' ); ?></button>
                        <button type="button" class="button aiwb-preview-tab" data-preview="tablet"><?php esc_html_e( 'Tablet', 'ai-ultimate-website-booster' ); ?></button>
                        <button type="button" class="button aiwb-preview-tab" data-preview="mobile"><?php esc_html_e( 'Mobile', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                    <div class="aiwb-template-preview aiwb-popup-preview aiwb-preview--desktop" id="aiwb-popup-preview"><?php esc_html_e( 'Popup preview area', 'ai-ultimate-website-booster' ); ?></div>
                </div>
            </div>
        <?php elseif ( 'health_scanner' === $active_tab ) : ?>
            <div class="aiwb-grid">
                <div class="aiwb-card">
                    <h3><?php esc_html_e( 'Health Score', 'ai-ultimate-website-booster' ); ?></h3>
                    <div class="aiwb-score" id="aiwb-health-score">0%</div>
                    <div class="aiwb-progress"><span id="aiwb-health-progress" style="width:0%"></span></div>
                </div>
                <div class="aiwb-card aiwb-card--full">
                    <h2><?php esc_html_e( 'Website Health Scanner', 'ai-ultimate-website-booster' ); ?></h2>
                    <div class="aiwb-action-row">
                        <button class="button button-primary" id="aiwb-health-scan-all"><?php esc_html_e( 'Run Full Security Scan', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-health-export-csv"><?php esc_html_e( 'Export CSV', 'ai-ultimate-website-booster' ); ?></button>
                        <button class="button" id="aiwb-health-export-pdf"><?php esc_html_e( 'Export PDF', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                    <div class="aiwb-health-list">
                        <div class="aiwb-health-item"><span><?php esc_html_e( 'Broken links', 'ai-ultimate-website-booster' ); ?></span><button class="button" id="aiwb-health-broken"><?php esc_html_e( 'Scan', 'ai-ultimate-website-booster' ); ?></button></div>
                        <div class="aiwb-health-item"><span><?php esc_html_e( 'Unused plugins', 'ai-ultimate-website-booster' ); ?></span><button class="button" id="aiwb-health-unused"><?php esc_html_e( 'Review', 'ai-ultimate-website-booster' ); ?></button></div>
                        <div class="aiwb-health-item"><span><?php esc_html_e( 'Database size', 'ai-ultimate-website-booster' ); ?></span><button class="button" id="aiwb-health-db"><?php esc_html_e( 'Check', 'ai-ultimate-website-booster' ); ?></button></div>
                        <div class="aiwb-health-item"><span><?php esc_html_e( 'Large images', 'ai-ultimate-website-booster' ); ?></span><button class="button" id="aiwb-health-images"><?php esc_html_e( 'Scan', 'ai-ultimate-website-booster' ); ?></button></div>
                        <div class="aiwb-health-item"><span><?php esc_html_e( 'Page speed suggestions', 'ai-ultimate-website-booster' ); ?></span><button class="button" id="aiwb-health-speed"><?php esc_html_e( 'View Tips', 'ai-ultimate-website-booster' ); ?></button></div>
                    </div>
                    <div class="aiwb-result" id="aiwb-health-result"></div>
                </div>
            </div>
        <?php elseif ( 'settings' === $active_tab ) : ?>
            <div class="aiwb-card aiwb-card--full">
                <h2><?php esc_html_e( 'Settings', 'ai-ultimate-website-booster' ); ?></h2>
                <p><?php esc_html_e( 'Manage API credentials and automation preferences.', 'ai-ultimate-website-booster' ); ?></p>
                <form method="post" action="options.php">
                    <?php settings_fields( 'aiwb_settings_group' ); ?>
                    <?php do_settings_sections( 'aiwb_settings_group' ); ?>
                    <table class="form-table aiwb-settings-table">
                        <tr>
                            <th><label for="api_provider"><?php esc_html_e( 'AI Provider', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td>
                                <select id="api_provider" name="aiwb_settings[api_provider]">
                                    <option value="openai" <?php selected( $settings['api_provider'], 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'ai-ultimate-website-booster' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="api_key"><?php esc_html_e( 'API Key', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td><input id="api_key" type="password" name="aiwb_settings[api_key]" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="api_model"><?php esc_html_e( 'AI Model', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td><input id="api_model" type="text" name="aiwb_settings[api_model]" value="<?php echo esc_attr( $settings['api_model'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="api_endpoint"><?php esc_html_e( 'API Endpoint', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td><input id="api_endpoint" type="url" name="aiwb_settings[api_endpoint]" value="<?php echo esc_attr( $settings['api_endpoint'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="image_provider"><?php esc_html_e( 'Image Provider', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td>
                                <select id="image_provider" name="aiwb_settings[image_provider]">
                                    <option value="" <?php selected( $settings['image_provider'], '' ); ?>><?php esc_html_e( 'Select provider', 'ai-ultimate-website-booster' ); ?></option>
                                    <option value="pixabay" <?php selected( $settings['image_provider'], 'pixabay' ); ?>><?php esc_html_e( 'Pixabay', 'ai-ultimate-website-booster' ); ?></option>
                                    <option value="pexels" <?php selected( $settings['image_provider'], 'pexels' ); ?>><?php esc_html_e( 'Pexels', 'ai-ultimate-website-booster' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="pixabay_api_key"><?php esc_html_e( 'Pixabay API Key', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td><input id="pixabay_api_key" type="password" name="aiwb_settings[pixabay_api_key]" value="<?php echo esc_attr( $settings['pixabay_api_key'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="pexels_api_key"><?php esc_html_e( 'Pexels API Key', 'ai-ultimate-website-booster' ); ?></label></th>
                            <td><input id="pexels_api_key" type="password" name="aiwb_settings[pexels_api_key]" value="<?php echo esc_attr( $settings['pexels_api_key'] ); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php endif; ?>
        <div class="aiwb-modal" id="aiwb-image-modal" aria-hidden="true" data-default-provider="<?php echo esc_attr( $settings['image_provider'] ); ?>">
            <div class="aiwb-modal__overlay"></div>
            <div class="aiwb-modal__panel" role="dialog" aria-modal="true" aria-labelledby="aiwb-image-modal-title">
                <div class="aiwb-modal__header">
                    <h3 id="aiwb-image-modal-title"><?php esc_html_e( 'Select Featured Image', 'ai-ultimate-website-booster' ); ?></h3>
                    <button type="button" class="aiwb-modal__close" id="aiwb-image-close" aria-label="<?php esc_attr_e( 'Close', 'ai-ultimate-website-booster' ); ?>">&times;</button>
                </div>
                <div class="aiwb-modal__controls">
                    <div class="aiwb-modal__providers">
                        <button type="button" class="aiwb-provider-btn" data-provider="pixabay"><?php esc_html_e( 'Pixabay', 'ai-ultimate-website-booster' ); ?></button>
                        <button type="button" class="aiwb-provider-btn" data-provider="pexels"><?php esc_html_e( 'Pexels', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                    <div class="aiwb-modal__search">
                        <input type="text" id="aiwb-image-query" placeholder="<?php esc_attr_e( 'Search images...', 'ai-ultimate-website-booster' ); ?>">
                        <button type="button" class="button" id="aiwb-image-search"><?php esc_html_e( 'Search', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                </div>
                <div class="aiwb-modal__body">
                    <div class="aiwb-image-grid" id="aiwb-image-grid"></div>
                    <div class="aiwb-modal__footer">
                        <button type="button" class="button" id="aiwb-image-load-more"><?php esc_html_e( 'Load More', 'ai-ultimate-website-booster' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="aiwb-modal" id="aiwb-schedule-modal" aria-hidden="true">
            <div class="aiwb-modal__overlay"></div>
            <div class="aiwb-modal__panel" role="dialog" aria-modal="true" aria-labelledby="aiwb-schedule-modal-title">
                <div class="aiwb-modal__header">
                    <h3 id="aiwb-schedule-modal-title"><?php esc_html_e( 'Schedule Bulk Posts', 'ai-ultimate-website-booster' ); ?></h3>
                    <button type="button" class="aiwb-modal__close" id="aiwb-schedule-close" aria-label="<?php esc_attr_e( 'Close', 'ai-ultimate-website-booster' ); ?>">&times;</button>
                </div>
                <div class="aiwb-modal__body">
                    <div class="aiwb-form-grid">
                        <label>
                            <span><?php esc_html_e( 'Schedule type', 'ai-ultimate-website-booster' ); ?></span>
                            <select id="aiwb-schedule-mode">
                                <option value="days"><?php esc_html_e( 'After X days', 'ai-ultimate-website-booster' ); ?></option>
                                <option value="date"><?php esc_html_e( 'On a specific date', 'ai-ultimate-website-booster' ); ?></option>
                            </select>
                        </label>
                        <label id="aiwb-schedule-days-field">
                            <span><?php esc_html_e( 'Days from today', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="number" id="aiwb-schedule-days" min="1" placeholder="2">
                        </label>
                        <label id="aiwb-schedule-date-field">
                            <span><?php esc_html_e( 'Schedule date', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="date" id="aiwb-schedule-date">
                        </label>
                        <label>
                            <span><?php esc_html_e( 'Schedule time', 'ai-ultimate-website-booster' ); ?></span>
                            <input type="time" id="aiwb-schedule-time" value="09:00">
                        </label>
                    </div>
                    <p class="aiwb-muted" id="aiwb-schedule-preview"><?php esc_html_e( 'Select a schedule to continue.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-modal__footer aiwb-modal__footer--actions">
                    <button type="button" class="button" id="aiwb-schedule-cancel"><?php esc_html_e( 'Cancel', 'ai-ultimate-website-booster' ); ?></button>
                    <button type="button" class="button button-primary" id="aiwb-schedule-apply"><?php esc_html_e( 'Apply Schedule', 'ai-ultimate-website-booster' ); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
