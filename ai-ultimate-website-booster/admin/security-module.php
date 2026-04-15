<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$module = isset( $aiwb_security_module ) && is_array( $aiwb_security_module ) ? $aiwb_security_module : array();
$module_key = $module['key'] ?? '';
$title = $module['title'] ?? __( 'Security Module', 'ai-ultimate-website-booster' );
$subtitle = $module['subtitle'] ?? __( 'Review security status and recent activity.', 'ai-ultimate-website-booster' );
$kpis = $module['kpis'] ?? array();
$check_labels = $module['checks'] ?? array();
$tips = $module['tips'] ?? array();
$recent_filter = $module['recent_filter'] ?? array();

$report = get_option( 'aiwb_last_security_report', array() );
$checks = $report['security']['checks'] ?? array();
$malware = $report['malware_scan'] ?? array();

function aiwb_get_check_item( $checks, $label ) {
    foreach ( $checks as $check ) {
        if ( isset( $check['label'] ) && $check['label'] === $label ) {
            return $check;
        }
    }
    return null;
}

function aiwb_get_check_status_class( $status ) {
    return $status === 'pass' ? 'aiwb-pill-good' : 'aiwb-pill-warn';
}

$recent = AIWB_Health::recent_actions( 12 );
if ( ! empty( $recent_filter ) ) {
    $recent = array_values( array_filter( $recent, function ( $row ) use ( $recent_filter ) {
        return in_array( $row['action_name'], $recent_filter, true );
    } ) );
}

?>

<div class="wrap aiwb-wrap">
    <div id="aiwb-toast" class="aiwb-toast" role="status" aria-live="polite"></div>
    <canvas id="aiwb-star-canvas" class="aiwb-star-canvas" aria-hidden="true"></canvas>
    <div class="aiwb-hero">
        <div class="aiwb-hero-brand">
            <img src="<?php echo esc_url( AIWB_URL . 'assets/images/logo.png' ); ?>" class="aiwb-hero-logo" alt="<?php esc_attr_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?>">
            <div>
                <h1><?php echo esc_html( $title ); ?></h1>
                <p class="aiwb-subtitle"><?php echo esc_html( $subtitle ); ?></p>
            </div>
        </div>
        <div class="aiwb-hero-actions">
            <button class="button button-primary aiwb-module-scan" data-module="<?php echo esc_attr( $module_key ); ?>"><?php esc_html_e( 'Run Module Scan', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button aiwb-module-export-csv" data-module="<?php echo esc_attr( $module_key ); ?>"><?php esc_html_e( 'Export Module CSV', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button aiwb-module-export-pdf" data-module="<?php echo esc_attr( $module_key ); ?>"><?php esc_html_e( 'Export Module PDF', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-health-scan-all"><?php esc_html_e( 'Run Full Security Scan', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-health-export-csv"><?php esc_html_e( 'Export CSV', 'ai-ultimate-website-booster' ); ?></button>
            <button class="button" id="aiwb-health-export-pdf"><?php esc_html_e( 'Export PDF', 'ai-ultimate-website-booster' ); ?></button>
        </div>
    </div>

    <?php if ( ! empty( $kpis ) ) : ?>
        <div class="aiwb-security-kpis">
            <?php foreach ( $kpis as $kpi ) :
                $value = $kpi['value'] ?? '-';
                if ( isset( $kpi['check_label'] ) ) {
                    $item = aiwb_get_check_item( $checks, $kpi['check_label'] );
                    $value = $item ? strtoupper( $item['status'] ) : __( 'Run Scan', 'ai-ultimate-website-booster' );
                }
                if ( isset( $kpi['data_key'] ) && is_array( $kpi['data_key'] ) ) {
                    $cursor = $report;
                    foreach ( $kpi['data_key'] as $key ) {
                        if ( is_array( $cursor ) && array_key_exists( $key, $cursor ) ) {
                            $cursor = $cursor[ $key ];
                        } else {
                            $cursor = null;
                            break;
                        }
                    }
                    if ( $cursor !== null ) {
                        $value = $cursor;
                    }
                }
            ?>
                <div class="aiwb-security-kpi <?php echo esc_attr( $kpi['class'] ?? '' ); ?>">
                    <strong><?php echo esc_html( $value ); ?></strong>
                    <span><?php echo esc_html( $kpi['label'] ?? '' ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="aiwb-security-grid">
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Module Status', 'ai-ultimate-website-booster' ); ?></h3>
            <?php if ( ! empty( $check_labels ) ) : ?>
                <div class="aiwb-security-list">
                    <?php foreach ( $check_labels as $label ) :
                        $item = aiwb_get_check_item( $checks, $label );
                        $status = $item['status'] ?? 'warn';
                    ?>
                        <div class="aiwb-security-item">
                            <span><?php echo esc_html( $label ); ?></span>
                            <span class="aiwb-pill <?php echo esc_attr( aiwb_get_check_status_class( $status ) ); ?>">
                                <?php echo esc_html( strtoupper( $status ) ); ?>
                            </span>
                            <span class="aiwb-muted"><?php echo esc_html( $item['detail'] ?? __( 'Run a scan to refresh.', 'ai-ultimate-website-booster' ) ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="aiwb-muted"><?php esc_html_e( 'Run a scan to view module details.', 'ai-ultimate-website-booster' ); ?></p>
            <?php endif; ?>
        </div>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Recommendations', 'ai-ultimate-website-booster' ); ?></h3>
            <?php if ( ! empty( $tips ) ) : ?>
                <ul class="aiwb-simple-list">
                    <?php foreach ( $tips as $tip ) : ?>
                        <li><?php echo esc_html( $tip ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="aiwb-muted"><?php esc_html_e( 'No recommendations yet. Run a scan for details.', 'ai-ultimate-website-booster' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $module_key === 'firewall' ) : ?>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Blocked IPs', 'ai-ultimate-website-booster' ); ?></h3>
            <div class="aiwb-firewall-form aiwb-firewall-form--block">
                <input type="text" id="aiwb-firewall-ip" placeholder="<?php esc_attr_e( 'IP Address', 'ai-ultimate-website-booster' ); ?>">
                <input type="text" id="aiwb-firewall-reason" placeholder="<?php esc_attr_e( 'Reason', 'ai-ultimate-website-booster' ); ?>">
                <input type="number" id="aiwb-firewall-duration" placeholder="<?php esc_attr_e( 'Duration (hours)', 'ai-ultimate-website-booster' ); ?>">
                <button class="button button-primary aiwb-firewall-block"><?php esc_html_e( 'Block IP', 'ai-ultimate-website-booster' ); ?></button>
            </div>
            <?php $fw_settings = get_option( 'aiwb_firewall_settings', array( 'auto_unblock' => '1', 'default_duration' => 24 ) ); ?>
            <div class="aiwb-firewall-settings">
                <label>
                    <input type="checkbox" id="aiwb-firewall-auto" <?php checked( ( $fw_settings['auto_unblock'] ?? '1' ), '1' ); ?>>
                    <?php esc_html_e( 'Auto-unblock expired rules', 'ai-ultimate-website-booster' ); ?>
                </label>
                <input type="number" id="aiwb-firewall-default-duration" value="<?php echo esc_attr( $fw_settings['default_duration'] ?? 24 ); ?>" min="1" placeholder="<?php esc_attr_e( 'Default duration (hours)', 'ai-ultimate-website-booster' ); ?>">
                <button class="button aiwb-firewall-save-settings"><?php esc_html_e( 'Save Rules', 'ai-ultimate-website-booster' ); ?></button>
            </div>
            <table class="widefat striped aiwb-firewall-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'IP', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Blocked At', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Expires', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'ai-ultimate-website-booster' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $blocked = AIWB_Health::get_blocked_ips( 30 );
                    if ( empty( $blocked ) ) :
                    ?>
                        <tr><td colspan="5"><?php esc_html_e( 'No blocked IPs yet.', 'ai-ultimate-website-booster' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $blocked as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['ip_address'] ); ?></td>
                                <td><?php echo esc_html( $row['reason'] ); ?></td>
                                <td><?php echo esc_html( $row['blocked_at'] ); ?></td>
                                <td><?php echo esc_html( $row['expires_at'] ? $row['expires_at'] : __( 'Never', 'ai-ultimate-website-booster' ) ); ?></td>
                                <td><button class="button aiwb-firewall-unblock" data-id="<?php echo esc_attr( $row['id'] ); ?>"><?php esc_html_e( 'Unblock', 'ai-ultimate-website-booster' ); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Allowlisted IPs', 'ai-ultimate-website-booster' ); ?></h3>
            <div class="aiwb-firewall-form aiwb-firewall-form--allow">
                <input type="text" id="aiwb-allow-ip" placeholder="<?php esc_attr_e( 'IP Address', 'ai-ultimate-website-booster' ); ?>">
                <input type="text" id="aiwb-allow-reason" placeholder="<?php esc_attr_e( 'Reason', 'ai-ultimate-website-booster' ); ?>">
                <button class="button button-primary aiwb-allowlist-add"><?php esc_html_e( 'Add to Allowlist', 'ai-ultimate-website-booster' ); ?></button>
            </div>
            <table class="widefat striped aiwb-allowlist-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'IP', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Added', 'ai-ultimate-website-booster' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'ai-ultimate-website-booster' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $allowed = AIWB_Health::get_allowlist_ips( 30 );
                    if ( empty( $allowed ) ) :
                    ?>
                        <tr><td colspan="4"><?php esc_html_e( 'No allowlisted IPs yet.', 'ai-ultimate-website-booster' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $allowed as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['ip_address'] ); ?></td>
                                <td><?php echo esc_html( $row['reason'] ); ?></td>
                                <td><?php echo esc_html( $row['created_at'] ); ?></td>
                                <td><button class="button aiwb-allowlist-remove" data-id="<?php echo esc_attr( $row['id'] ); ?>"><?php esc_html_e( 'Remove', 'ai-ultimate-website-booster' ); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ( $module_key === 'integrity' ) : ?>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Integrity Baseline', 'ai-ultimate-website-booster' ); ?></h3>
            <p class="aiwb-muted"><?php esc_html_e( 'Rebuild the baseline to compare current core files against a known-good snapshot.', 'ai-ultimate-website-booster' ); ?></p>
            <div class="aiwb-security-actions">
                <button class="button aiwb-integrity-rebuild"><?php esc_html_e( 'Rebuild Baseline', 'ai-ultimate-website-booster' ); ?></button>
                <button class="button button-primary aiwb-integrity-scan"><?php esc_html_e( 'Run Integrity Scan', 'ai-ultimate-website-booster' ); ?></button>
            </div>
            <div id="aiwb-integrity-result" class="aiwb-result"></div>
        </div>
    <?php endif; ?>

    <?php if ( $module_key === 'malware' ) : ?>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Scan Exclusions', 'ai-ultimate-website-booster' ); ?></h3>
            <p class="aiwb-muted"><?php esc_html_e( 'Exclude trusted paths from malware scans.', 'ai-ultimate-website-booster' ); ?></p>
            <div class="aiwb-firewall-form">
                <input type="text" id="aiwb-malware-exclusion" placeholder="<?php esc_attr_e( 'Relative path (e.g. uploads/cache)', 'ai-ultimate-website-booster' ); ?>">
                <button class="button button-primary aiwb-malware-add-exclusion"><?php esc_html_e( 'Add Exclusion', 'ai-ultimate-website-booster' ); ?></button>
            </div>
            <ul class="aiwb-simple-list" id="aiwb-malware-exclusions">
                <?php
                $exclusions = AIWB_Health::get_malware_exclusions();
                if ( empty( $exclusions ) ) :
                ?>
                    <li><?php esc_html_e( 'No exclusions added.', 'ai-ultimate-website-booster' ); ?></li>
                <?php else : ?>
                    <?php foreach ( $exclusions as $path ) : ?>
                        <li>
                            <?php echo esc_html( $path ); ?>
                            <button class="button aiwb-malware-remove-exclusion" data-path="<?php echo esc_attr( $path ); ?>"><?php esc_html_e( 'Remove', 'ai-ultimate-website-booster' ); ?></button>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $module['show_samples'] ) ) : ?>
        <div class="aiwb-card">
            <h3><?php esc_html_e( 'Sample Findings', 'ai-ultimate-website-booster' ); ?></h3>
            <?php if ( ! empty( $malware['samples'] ) ) : ?>
                <ul class="aiwb-simple-list">
                    <?php foreach ( $malware['samples'] as $sample ) : ?>
                        <li><?php echo esc_html( $sample ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="aiwb-muted"><?php esc_html_e( 'No malware findings captured yet.', 'ai-ultimate-website-booster' ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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

    <div class="aiwb-card" id="aiwb-health-result" data-module="<?php echo esc_attr( $module_key ); ?>"></div>
</div>
