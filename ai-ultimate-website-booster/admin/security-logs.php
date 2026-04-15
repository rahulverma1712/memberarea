<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logs = AIWB_Health::recent_actions( 60 );
$actions = array();
foreach ( $logs as $row ) {
    if ( isset( $row['action_name'] ) ) {
        $actions[ $row['action_name'] ] = true;
    }
}
ksort( $actions );
?>

<div class="wrap aiwb-wrap">
    <div id="aiwb-toast" class="aiwb-toast" role="status" aria-live="polite"></div>
    <canvas id="aiwb-star-canvas" class="aiwb-star-canvas" aria-hidden="true"></canvas>
    <div class="aiwb-hero">
        <div>
            <h1><?php esc_html_e( 'Security Logs', 'ai-ultimate-website-booster' ); ?></h1>
            <p class="aiwb-subtitle"><?php esc_html_e( 'Audit security events, scans, and administrative actions.', 'ai-ultimate-website-booster' ); ?></p>
        </div>
        <div class="aiwb-hero-actions">
            <button class="button button-primary aiwb-clear-logs"><?php esc_html_e( 'Clear Logs', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button aiwb-logs-export-csv"><?php esc_html_e( 'Export CSV', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button aiwb-logs-export-pdf"><?php esc_html_e( 'Export PDF', 'ai-ultimate-website-booster' ); ?></button>
        </div>
    </div>

    <div class="aiwb-card">
        <div class="aiwb-log-toolbar">
            <label for="aiwb-log-filter"><?php esc_html_e( 'Filter by event', 'ai-ultimate-website-booster' ); ?></label>
            <select id="aiwb-log-filter">
                <option value=""><?php esc_html_e( 'All events', 'ai-ultimate-website-booster' ); ?></option>
                <?php foreach ( $actions as $action => $enabled ) : ?>
                    <option value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $action ) ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="widefat striped aiwb-log-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Time', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'Event', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'IP Address', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'ai-ultimate-website-booster' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No log entries yet.', 'ai-ultimate-website-booster' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $logs as $row ) :
                        $data = json_decode( $row['action_data'], true );
                        $ip = $data['ip'] ?? '-';
                        $message = $data['message'] ?? $row['action_name'];
                        if ( $row['action_name'] === 'login_failed' ) {
                            $message = sprintf( __( 'Login failed for %s', 'ai-ultimate-website-booster' ), $data['username'] ?? '-' );
                        } elseif ( $row['action_name'] === 'login_success' ) {
                            $message = sprintf( __( 'Login success for %s', 'ai-ultimate-website-booster' ), $data['username'] ?? '-' );
                        } elseif ( $row['action_name'] === 'full_security' ) {
                            $message = __( 'Full security scan completed.', 'ai-ultimate-website-booster' );
                        } elseif ( $row['action_name'] === 'module_security' ) {
                            $message = sprintf( __( 'Module scan completed (%s).', 'ai-ultimate-website-booster' ), $data['module'] ?? '-' );
                        } elseif ( $row['action_name'] === 'firewall_block' ) {
                            $message = __( 'Firewall blocked a request.', 'ai-ultimate-website-booster' );
                        }
                    ?>
                        <tr data-action="<?php echo esc_attr( $row['action_name'] ); ?>">
                            <td><?php echo esc_html( $row['created_at'] ); ?></td>
                            <td><?php echo esc_html( ucwords( str_replace( '_', ' ', $row['action_name'] ) ) ); ?></td>
                            <td><?php echo esc_html( $ip ); ?></td>
                            <td><?php echo esc_html( $message ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
