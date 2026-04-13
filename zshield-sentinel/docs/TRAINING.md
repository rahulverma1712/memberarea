# ZShield Sentinel Security - Training Guide

This guide is designed for admins, support teams, and site managers who will operate the ZShield Sentinel Security plugin. It covers daily use, common workflows, and safe operating practices.

## 1) Getting Started

1. Go to `WP Admin > ZShield > Dashboard`.
2. Review the **Security Score** and **Protection Status**.
3. Open **Settings** and review all toggles before enabling.
4. Create a **File Integrity Baseline** once the site is confirmed clean.

## 2) Core Features Overview

### Login Guard
- Limits login attempts and locks out IPs after repeated failures.
- Configure **Max Attempts** and **Lockout Minutes** in Settings.

### Two-Factor Authentication (TOTP)
- Enable 2FA globally, then users can enable it in their profile.
- Requires a 6-digit code during login.

### Login Captcha
- Adds a math challenge or reCAPTCHA to the login form.
- Helps reduce bot-based attacks.

### Login URL Hardening
- Restricts login access to a custom URL slug or access key.
- Prevents direct access to wp-login.php without authorization.

### Security Hardening
- Disable XML-RPC and Pingbacks.
- Block Author Enumeration.
- Disable File Editor.
- Hide WordPress Version.
- Enable Security Headers.

### Firewall
- Blocks common exploit patterns in requests.
- Supports allowlist/blocklist IPs and excluded paths.

### Audit Log
- Tracks security events such as failed logins, lockouts, and scans.
- Use it weekly to confirm no unusual activity.
- Export CSV for offline review when needed.

### File Integrity
- Compares files against a trusted baseline.
- Detects **Added**, **Modified**, or **Removed** files.
- Use **Diff Preview** to inspect changes.
- Optional WordPress core integrity scan.

### Malware Scan
- Heuristic scan for suspicious code patterns.
- Findings show severity, rule name, file path, and suggestions.
- Scheduled malware scans and email reports are available.
- Quarantine isolates suspicious files with restore support.

### Security Score
- Provides a high-level health score for quick assessment.
- Improve score by enabling recommended protections.

## 3) Recommended Weekly Workflow

1. Check **Dashboard** for status changes.
2. Review **Audit Log** for failed login spikes or lockouts.
3. Run **Malware Scan** and review findings.
4. Run **File Integrity Scan** after any update.

## 4) Baseline and Scan Procedures

### Create Baseline
1. Ensure the site is clean and up to date.
2. Go to **File Integrity**.
3. Click **Create Baseline**.

### Run Scan
1. Go to **File Integrity**.
2. Click **Run Scan**.
3. Review results and open **Diff Preview** where available.

### When to Recreate Baseline
- After intentional updates to themes/plugins or custom code.
- Do **not** recreate if unknown changes are detected.

## 5) Malware Scan Procedures

1. Go to **Malware Scan**.
2. Click **Run Malware Scan**.
3. Filter by severity and search for suspicious files.
4. Confirm false positives before taking action.

## 6) Alerts and Emails

### Login Lockout Alerts
- Enable **Email Alerts** and set **Alert Email**.

### Scan Reports
- Enable **Scan Email Reports** and set **Scan Report Email**.

### Malware Reports
- Enable **Malware Email Reports** and set **Malware Report Email**.

## 7) Malware Scan Tuning

### Ignore Vendor JS
- Skips minified bundles to reduce false positives.

### Rule Allowlist
- Add known-safe rule keys (comma-separated).

### Path Allowlist
- Add trusted paths, one per line.

### Uploads and MU-Plugins
- Enable scanning of uploads and MU-plugins for broader coverage.

## 8) Common Scenarios

### Many File Changes After Updates
- Verify updates were expected.
- Review diffs and then recreate baseline.

### Malware Scan Flags Core Files
- Re-check file source.
- Compare with official WordPress or plugin copies.

### No Emails Received
- Verify SMTP configuration.
- Test WordPress email delivery.

### Login Page Not Accessible
- Use the custom login slug or access key if Login Hardening is enabled.
- Disable the setting if the URL is lost.

## 9) Best Practices

- Keep WordPress core, themes, and plugins updated.
- Use strong passwords and 2FA.
- Limit admin accounts to trusted users.
- Review logs after major changes.

## 10) Support Checklist

Before contacting support, collect:
- Plugin version
- WordPress version
- Screenshots of the issue
- Recent scan reports or audit log entries

---

If you want this document in HTML format for the built-in Docs page, tell me and I’ll convert it.
