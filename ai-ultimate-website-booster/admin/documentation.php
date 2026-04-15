<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'AIWB_Ajax' ) ) {
    AIWB_Ajax::log_action( 'open_docs', array( 'source' => 'admin' ) );
}

$version = defined( 'AIWB_VERSION' ) ? AIWB_VERSION : '1.0.0';
?>

<div class="wrap aiwb-wrap">
    <div id="aiwb-toast" class="aiwb-toast" role="status" aria-live="polite"></div>
    <canvas id="aiwb-star-canvas" class="aiwb-star-canvas" aria-hidden="true"></canvas>

    <div class="aiwb-hero aiwb-docs-hero">
        <div class="aiwb-hero-brand">
            <img src="<?php echo esc_url( AIWB_URL . 'assets/images/logo.png' ); ?>" class="aiwb-hero-logo" alt="<?php esc_attr_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?>">
            <div>
                <h1><?php esc_html_e( 'AI Ultimate Website Booster Documentation', 'ai-ultimate-website-booster' ); ?></h1>
                <p class="aiwb-subtitle"><?php esc_html_e( 'A complete module-by-module guide in the same order as the admin menu, written for practical day-to-day use.', 'ai-ultimate-website-booster' ); ?></p>
            </div>
        </div>
        <div class="aiwb-hero-actions">
            <a href="#aiwb-doc-dashboard" class="button button-primary"><?php esc_html_e( 'Start From Dashboard', 'ai-ultimate-website-booster' ); ?></a>
            <a href="#aiwb-doc-settings" class="button"><?php esc_html_e( 'Go To Settings', 'ai-ultimate-website-booster' ); ?></a>
        </div>
    </div>

    <div class="aiwb-docs-layout">
        <aside class="aiwb-docs-sidebar">
            <div class="aiwb-docs-brand">
                <div>
                    <strong><?php esc_html_e( 'AIWB Docs', 'ai-ultimate-website-booster' ); ?></strong>
                    <span><?php echo esc_html( sprintf( __( 'Version %s', 'ai-ultimate-website-booster' ), $version ) ); ?></span>
                </div>
            </div>

            <div class="aiwb-docs-nav">
                <p class="aiwb-docs-nav-label"><?php esc_html_e( 'Navigation Order', 'ai-ultimate-website-booster' ); ?></p>
                <a href="#aiwb-doc-dashboard"><?php esc_html_e( 'Dashboard', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-settings"><?php esc_html_e( 'Settings', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-ai-content"><?php esc_html_e( 'AI Content', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-ai-ideas"><?php esc_html_e( 'AI Blog Ideas', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-bulk"><?php esc_html_e( 'Bulk Post Generator', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-seo"><?php esc_html_e( 'SEO Tools', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-popups"><?php esc_html_e( 'Popups', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-health"><?php esc_html_e( 'Health Scanner', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-security-overview"><?php esc_html_e( 'Security Overview', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-login"><?php esc_html_e( 'Login Security', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-firewall"><?php esc_html_e( 'Firewall', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-integrity"><?php esc_html_e( 'File Integrity', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-malware"><?php esc_html_e( 'Malware Scanner', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-hardening"><?php esc_html_e( 'Hardening', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-headers"><?php esc_html_e( 'Security Headers', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-logs"><?php esc_html_e( 'Security Logs', 'ai-ultimate-website-booster' ); ?></a>
                <a href="#aiwb-doc-documentation"><?php esc_html_e( 'Documentation', 'ai-ultimate-website-booster' ); ?></a>

                <p class="aiwb-docs-nav-label"><?php esc_html_e( 'Support', 'ai-ultimate-website-booster' ); ?></p>
                <a href="#aiwb-doc-troubleshooting"><?php esc_html_e( 'Troubleshooting', 'ai-ultimate-website-booster' ); ?></a>
            </div>
        </aside>

        <main class="aiwb-docs-main">
            <section id="aiwb-doc-dashboard" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '1. Dashboard', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Use this page as your daily control center for performance, AI usage, and live module status.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-grid">
                    <div class="aiwb-docs-card">
                        <h3><?php esc_html_e( 'What You See', 'ai-ultimate-website-booster' ); ?></h3>
                        <ul>
                            <li><?php esc_html_e( 'Total AI posts generated and posts updated.', 'ai-ultimate-website-booster' ); ?></li>
                            <li><?php esc_html_e( 'Average SEO score and health score indicators.', 'ai-ultimate-website-booster' ); ?></li>
                            <li><?php esc_html_e( 'Live module cards with readiness and latest activity.', 'ai-ultimate-website-booster' ); ?></li>
                        </ul>
                    </div>
                    <div class="aiwb-docs-card">
                        <h3><?php esc_html_e( 'Best Practice', 'ai-ultimate-website-booster' ); ?></h3>
                        <p><?php esc_html_e( 'Review dashboard metrics first, then open only the modules that show warnings to save time and focus on priority tasks.', 'ai-ultimate-website-booster' ); ?></p>
                    </div>
                </div>
            </section>

            <section id="aiwb-doc-settings" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '2. Settings', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Complete setup here before using AI generation, image providers, popups, and automation.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <h3><?php esc_html_e( 'Step-by-Step Setup', 'ai-ultimate-website-booster' ); ?></h3>
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Select API provider and enter your API key.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Choose a model for content generation.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Configure image provider (Pixabay or Pexels) keys if you want featured image suggestions.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Save changes and verify module readiness on Dashboard.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-ai-content" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '3. AI Content', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Generate a full post draft from a topic with controllable tone, length, and keyword focus.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Enter topic and choose tone/content length.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Generate content and review inside editor.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Open post settings and choose status (draft, publish, or schedule).', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Save post and validate final formatting.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-ai-ideas" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '4. AI Blog Ideas', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Quickly create topic options from a seed keyword and convert one idea into a full post.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Enter a keyword and click Generate Ideas.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Select the best idea from the list.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Click Convert Idea to Post to continue in AI Content.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-bulk" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '5. Bulk Post Generator', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Generate multiple post drafts in one run and save them as draft/publish/schedule with optional featured images.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Paste keywords one per line and set number of posts.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Click Generate Posts (or Generate and Schedule).', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Review each generated title/content and attach featured images if needed.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Choose post status and save all posts.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-seo" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '6. SEO Tools', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Generate and store SEO metadata plus schema details for stronger search visibility.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Create title, meta description, and focus keyword suggestions.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Generate FAQ/schema content for selected posts.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Save SEO data directly to post metadata.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-popups" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '7. Popups', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Create conversion-focused popup campaigns with trigger and device controls.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Choose template and message.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Set trigger type: exit-intent, scroll, or time delay.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Define CTA button text/url and enable campaign.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-health" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '8. Health Scanner', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Run a full scan to receive dynamic reports for security checks, links, assets, database, malware summary, and speed tips.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Click Run Full Security Scan.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Review report cards and warnings.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Export CSV/PDF when you need client or team reporting.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-security-overview" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '9. Security Overview', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Central security dashboard showing KPIs, module status, and recent activity timeline.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Use this page for high-level monitoring.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Run full scans and then open specific security modules for remediation.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-login" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '10. Login Security', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Track login failures and validate brute-force protection controls.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Review failed login trends.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Ensure admin account hardening and safe login policy.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-firewall" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '11. Firewall', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Manage blocked IPs, allowlist entries, and firewall policy settings.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Block suspicious IP addresses with reason and duration.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Allow trusted IPs for admin-safe access.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Use recent activity section for incident tracking.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-integrity" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '12. File Integrity', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Create baseline checks and detect changed or suspicious core files.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ol class="aiwb-docs-steps">
                        <li><?php esc_html_e( 'Build or rebuild baseline.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Run integrity scan.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Review issue list and reconcile legitimate file changes.', 'ai-ultimate-website-booster' ); ?></li>
                    </ol>
                </div>
            </section>

            <section id="aiwb-doc-malware" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '13. Malware Scanner', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Run pattern-based malware scans for plugins and themes with exclusion support.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Add safe exclusions to reduce false positives.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Run module scan and review findings/sample paths.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-hardening" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '14. Hardening', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Validate essential hardening controls like XML-RPC, debug mode, and file editor restrictions.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Review hardening checks regularly after updates.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Keep production-safe defaults enabled.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-headers" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '15. Security Headers', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Check HTTP header coverage and transport policies required for stronger browser-side protection.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Verify X-Frame-Options, HSTS, and CSP visibility.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Resolve WARN states with hosting/server configuration.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-logs" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '16. Security Logs', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Audit trail for security events with filtering and export options.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'Filter by event type for faster investigations.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'Export logs for team handoff or compliance records.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <section id="aiwb-doc-documentation" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( '17. Documentation', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'This section is your built-in product manual. Keep it as the final stop for onboarding and troubleshooting references.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <p><?php esc_html_e( 'Recommended team flow: new users should follow modules in this exact menu order to reduce confusion and setup mistakes.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
            </section>

            <section id="aiwb-doc-troubleshooting" class="aiwb-docs-section">
                <div class="aiwb-docs-section-head">
                    <h2><?php esc_html_e( 'Troubleshooting', 'ai-ultimate-website-booster' ); ?></h2>
                    <p><?php esc_html_e( 'Quick checks for common issues.', 'ai-ultimate-website-booster' ); ?></p>
                </div>
                <div class="aiwb-docs-card">
                    <ul>
                        <li><?php esc_html_e( 'If content is not generating, verify API key/model in Settings.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'If report is empty, run a fresh full scan and retry export.', 'ai-ultimate-website-booster' ); ?></li>
                        <li><?php esc_html_e( 'If editor controls do not update, reload admin page and clear browser cache.', 'ai-ultimate-website-booster' ); ?></li>
                    </ul>
                </div>
            </section>

            <div class="aiwb-docs-footer">
                <strong><?php esc_html_e( 'AI Ultimate Website Booster', 'ai-ultimate-website-booster' ); ?></strong>
                <span><?php echo esc_html( sprintf( __( 'Version %s | Professional module guide aligned with admin menu sequence.', 'ai-ultimate-website-booster' ), $version ) ); ?></span>
            </div>
        </main>
    </div>
</div>

<script>
    (function () {
        var docsRoot = document.querySelector('.aiwb-docs-layout');
        if (!docsRoot) {
            return;
        }

        var navLinks = Array.prototype.slice.call(document.querySelectorAll('.aiwb-docs-nav a[href^="#"]'));
        var heroLinks = Array.prototype.slice.call(document.querySelectorAll('.aiwb-docs-hero a[href^="#"]'));
        var allLinks = navLinks.concat(heroLinks);
        if (!allLinks.length) {
            return;
        }

        var idToSection = {};
        navLinks.forEach(function (link) {
            var hash = link.getAttribute('href') || '';
            var id = hash.replace('#', '');
            if (!id) {
                return;
            }
            var section = document.getElementById(id);
            if (section) {
                idToSection[id] = section;
            }
        });

        var scrollContainer = document.querySelector('.aiwb-docs-main');
        var useContainerScroll = !!scrollContainer;

        function getLocalOffset() {
            return 10;
        }

        function setActiveLink(id) {
            navLinks.forEach(function (link) {
                var isActive = link.getAttribute('href') === '#' + id;
                link.classList.toggle('active', isActive);
            });
        }

        function scrollToSection(id, updateHash) {
            if (!idToSection[id]) {
                return;
            }
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (useContainerScroll) {
                var containerRect = scrollContainer.getBoundingClientRect();
                var sectionRect = idToSection[id].getBoundingClientRect();
                var targetTop = Math.max(
                    0,
                    scrollContainer.scrollTop + sectionRect.top - containerRect.top - getLocalOffset()
                );
                scrollContainer.scrollTo({
                    top: targetTop,
                    behavior: reduceMotion ? 'auto' : 'smooth'
                });
            } else {
                var topPad = 18;
                var adminBar = document.getElementById('wpadminbar');
                if (adminBar) {
                    topPad += adminBar.offsetHeight || 0;
                }
                var fallbackTop = Math.max(
                    0,
                    window.pageYOffset + idToSection[id].getBoundingClientRect().top - topPad
                );
                window.scrollTo({
                    top: fallbackTop,
                    behavior: reduceMotion ? 'auto' : 'smooth'
                });
            }

            if (updateHash && window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#' + id);
            }

            setActiveLink(id);
        }

        allLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var hash = link.getAttribute('href') || '';
                var id = hash.replace('#', '');
                if (!id || !idToSection[id]) {
                    return;
                }
                event.preventDefault();
                scrollToSection(id, true);
            });
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        setActiveLink(entry.target.id);
                    }
                });
            }, {
                root: useContainerScroll ? scrollContainer : null,
                rootMargin: useContainerScroll ? '-8% 0px -78% 0px' : '-30% 0px -55% 0px',
                threshold: 0
            });

            Object.keys(idToSection).forEach(function (id) {
                observer.observe(idToSection[id]);
            });
        } else {
            var fallbackRoot = useContainerScroll ? scrollContainer : window;
            fallbackRoot.addEventListener('scroll', function () {
                var currentId = '';
                var scrollPos = useContainerScroll ? (scrollContainer.scrollTop + 20) : (window.pageYOffset + 20);
                Object.keys(idToSection).forEach(function (id) {
                    var sectionTop = useContainerScroll
                        ? (idToSection[id].offsetTop - scrollContainer.offsetTop)
                        : idToSection[id].offsetTop;
                    if (sectionTop <= scrollPos) {
                        currentId = id;
                    }
                });
                if (currentId) {
                    setActiveLink(currentId);
                }
            }, { passive: true });
        }

        if (window.location.hash) {
            var startupId = window.location.hash.replace('#', '');
            if (idToSection[startupId]) {
                window.setTimeout(function () {
                    scrollToSection(startupId, false);
                }, 30);
                return;
            }
        }

        var firstId = Object.keys(idToSection)[0];
        if (firstId) {
            setActiveLink(firstId);
        }
    })();
</script>
