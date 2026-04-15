<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$report = get_option( 'aiwb_last_security_report', array() );
$checks = $report['security']['checks'] ?? array();

function aiwb_find_check_status( $checks, $label ) {
    foreach ( $checks as $check ) {
        if ( isset( $check['label'] ) && $check['label'] === $label ) {
            return $check['status'];
        }
    }
    return 'warn';
}

$failed_logins = AIWB_Health::count_action( 'login_failed', 7 );
$success_logins = AIWB_Health::count_action( 'login_success', 7 );
$firewall_blocks = AIWB_Health::count_action( 'firewall_block', 7 );
$malware_issues = isset( $report['malware_scan']['findings'] ) ? (int) $report['malware_scan']['findings'] : 0;

$core_issue_count = 0;
foreach ( $checks as $check ) {
    if ( isset( $check['label'] ) && $check['label'] === __( 'Core File Integrity', 'ai-ultimate-website-booster' ) ) {
        $core_issue_count = $check['status'] === 'warn' ? 1 : 0;
    }
}

$module_status = array(
    array(
        'label' => __( 'Login Security', 'ai-ultimate-website-booster' ),
        'status' => aiwb_find_check_status( $checks, __( 'Brute-force Protection', 'ai-ultimate-website-booster' ) ),
        'url' => admin_url( 'admin.php?page=aiwb-security-login' ),
    ),
    array(
        'label' => __( 'Firewall', 'ai-ultimate-website-booster' ),
        'status' => aiwb_find_check_status( $checks, __( 'WAF / Firewall Detected', 'ai-ultimate-website-booster' ) ),
        'url' => admin_url( 'admin.php?page=aiwb-security-firewall' ),
    ),
    array(
        'label' => __( 'File Integrity', 'ai-ultimate-website-booster' ),
        'status' => aiwb_find_check_status( $checks, __( 'Core File Integrity', 'ai-ultimate-website-booster' ) ),
        'url' => admin_url( 'admin.php?page=aiwb-security-integrity' ),
    ),
    array(
        'label' => __( 'Malware Scanner', 'ai-ultimate-website-booster' ),
        'status' => $malware_issues > 0 ? 'warn' : 'pass',
        'url' => admin_url( 'admin.php?page=aiwb-security-malware' ),
    ),
    array(
        'label' => __( 'Hardening', 'ai-ultimate-website-booster' ),
        'status' => aiwb_find_check_status( $checks, __( 'XML-RPC Disabled', 'ai-ultimate-website-booster' ) ),
        'url' => admin_url( 'admin.php?page=aiwb-security-hardening' ),
    ),
    array(
        'label' => __( 'Sec. Headers', 'ai-ultimate-website-booster' ),
        'status' => aiwb_find_check_status( $checks, __( 'Security Headers (XFO/CSP/HSTS)', 'ai-ultimate-website-booster' ) ),
        'url' => admin_url( 'admin.php?page=aiwb-security-headers' ),
    ),
);

$recent = AIWB_Health::recent_actions( 10 );
$recent = array_values( array_filter( $recent, function ( $row ) {
    return in_array( $row['action_name'], array( 'login_failed', 'login_success', 'firewall_block', 'full_security' ), true );
} ) );
?>

<div class="wrap aiwb-wrap">
    <div id="aiwb-toast" class="aiwb-toast" role="status" aria-live="polite"></div>
    <canvas id="aiwb-star-canvas" class="aiwb-star-canvas" aria-hidden="true"></canvas>
    <div class="aiwb-hero">
        <div class="aiwb-hero-brand">
            <img src="<?php echo esc_url( AIWB_URL . 'assets/images/logo.png' ); ?>" class="aiwb-hero-logo" alt="<?php esc_attr_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?>">
            <div>
                <h1><?php esc_html_e( 'Security Overview', 'ai-ultimate-website-booster' ); ?></h1>
                <p class="aiwb-subtitle"><?php esc_html_e( 'Run a full security scan, review module health, and monitor recent activity.', 'ai-ultimate-website-booster' ); ?></p>
            </div>
        </div>
        <div class="aiwb-hero-actions">
            <button class="button button-primary" id="aiwb-health-scan-all"><?php esc_html_e( 'Run Full Security Scan', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-health-export-csv"><?php esc_html_e( 'Export CSV', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-health-export-pdf"><?php esc_html_e( 'Export PDF', 'ai-ultimate-website-booster' ); ?></button>
        </div>
    </div>

    <div class="aiwb-security-kpis">
        <div class="aiwb-security-kpi is-danger">
            <strong><?php echo esc_html( $failed_logins ); ?></strong>
            <span><?php esc_html_e( 'Failed Logins (7d)', 'ai-ultimate-website-booster' ); ?></span>
        </div>
        <div class="aiwb-security-kpi is-good">
            <strong><?php echo esc_html( $success_logins ); ?></strong>
            <span><?php esc_html_e( 'Successful Logins (7d)', 'ai-ultimate-website-booster' ); ?></span>
        </div>
        <div class="aiwb-security-kpi is-warn">
            <strong><?php echo esc_html( $firewall_blocks ); ?></strong>
            <span><?php esc_html_e( 'Firewall Blocks (7d)', 'ai-ultimate-website-booster' ); ?></span>
        </div>
        <div class="aiwb-security-kpi is-warn">
            <strong><?php echo esc_html( $malware_issues ); ?></strong>
            <span><?php esc_html_e( 'Malware Issues (active)', 'ai-ultimate-website-booster' ); ?></span>
        </div>
        <div class="aiwb-security-kpi is-info">
            <strong><?php echo esc_html( $core_issue_count ); ?></strong>
            <span><?php esc_html_e( 'File Anomalies', 'ai-ultimate-website-booster' ); ?></span>
        </div>
        <div class="aiwb-security-kpi is-info">
            <strong><?php echo esc_html( $firewall_blocks ); ?></strong>
            <span><?php esc_html_e( 'IPs Blocked (7d)', 'ai-ultimate-website-booster' ); ?></span>
        </div>
    </div>

    <div class="aiwb-security-grid">
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Module Status', 'ai-ultimate-website-booster' ); ?></h3>
            <div class="aiwb-security-list">
                <?php foreach ( $module_status as $module ) : ?>
                    <div class="aiwb-security-item">
                        <span><?php echo esc_html( $module['label'] ); ?></span>
                        <span class="aiwb-pill <?php echo $module['status'] === 'pass' ? 'aiwb-pill-good' : 'aiwb-pill-warn'; ?>">
                            <?php echo $module['status'] === 'pass' ? esc_html__( 'Active', 'ai-ultimate-website-booster' ) : esc_html__( 'Needs Attention', 'ai-ultimate-website-booster' ); ?>
                        </span>
                        <a href="<?php echo esc_url( $module['url'] ?? '#aiwb-health-result' ); ?>" class="aiwb-link"><?php esc_html_e( 'Configure', 'ai-ultimate-website-booster' ); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Quick Actions', 'ai-ultimate-website-booster' ); ?></h3>
            <div class="aiwb-security-actions">
                <button class="button button-primary aiwb-health-scan-all-quick"><?php esc_html_e( 'Run Security Scan Now', 'ai-ultimate-website-booster' ); ?></button>
                <button class="button aiwb-health-export-csv"><?php esc_html_e( 'Export CSV', 'ai-ultimate-website-booster' ); ?></button>
                <button class="button aiwb-health-export-pdf"><?php esc_html_e( 'Export PDF', 'ai-ultimate-website-booster' ); ?></button>
            </div>
        </div>
    </div>

    <div class="aiwb-card">
        <h3><?php esc_html_e( 'Recent Activity', 'ai-ultimate-website-booster' ); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Time', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'Event', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'IP Address', 'ai-ultimate-website-booster' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'ai-ultimate-website-booster' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $recent ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No recent activity.', 'ai-ultimate-website-booster' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $recent as $row ) :
                        $data = json_decode( $row['action_data'], true );
                        $ip = $data['ip'] ?? '-';
                        $message = $data['message'] ?? $row['action_name'];
                        if ( $row['action_name'] === 'login_failed' ) {
                            $message = sprintf( __( 'Login failed for %s', 'ai-ultimate-website-booster' ), $data['username'] ?? '-' );
                        } elseif ( $row['action_name'] === 'login_success' ) {
                            $message = sprintf( __( 'Login success for %s', 'ai-ultimate-website-booster' ), $data['username'] ?? '-' );
                        } elseif ( $row['action_name'] === 'full_security' ) {
                            $message = __( 'Full security scan completed.', 'ai-ultimate-website-booster' );
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html( $row['created_at'] ); ?></td>
                            <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $row['action_name'] ) ) ); ?></td>
                            <td><?php echo esc_html( $ip ); ?></td>
                            <td><?php echo esc_html( $message ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="aiwb-card" id="aiwb-health-result"></div>

    <div class="aiwb-card">
        <h3><?php esc_html_e( 'Scheduled Security Scans', 'ai-ultimate-website-booster' ); ?></h3>
        <?php $schedule = get_option( 'aiwb_security_schedule', array( 'enabled' => '0', 'frequency' => 'weekly', 'hour' => 2 ) ); ?>
        <div class="aiwb-firewall-settings">
            <label>
                <input type="checkbox" id="aiwb-scan-enabled" <?php checked( ( $schedule['enabled'] ?? '0' ), '1' ); ?>>
                <?php esc_html_e( 'Enable automated full security scans', 'ai-ultimate-website-booster' ); ?>
            </label>
            <select id="aiwb-scan-frequency">
                <option value="daily" <?php selected( ( $schedule['frequency'] ?? 'weekly' ), 'daily' ); ?>><?php esc_html_e( 'Daily', 'ai-ultimate-website-booster' ); ?></option>
                <option value="weekly" <?php selected( ( $schedule['frequency'] ?? 'weekly' ), 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'ai-ultimate-website-booster' ); ?></option>
            </select>
            <select id="aiwb-scan-hour">
                <?php for ( $h = 0; $h < 24; $h++ ) : ?>
                    <option value="<?php echo esc_attr( $h ); ?>" <?php selected( (int) ( $schedule['hour'] ?? 2 ), $h ); ?>>
                        <?php echo esc_html( sprintf( '%02d:00', $h ) ); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <button class="button aiwb-save-scan-schedule"><?php esc_html_e( 'Save Schedule', 'ai-ultimate-website-booster' ); ?></button>
        </div>
    </div>
</div>
