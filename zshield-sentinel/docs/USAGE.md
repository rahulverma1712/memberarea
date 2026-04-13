# ZShield Sentinel Security - Usage Guide

This guide explains how to use the plugin step by step.

## 1) Install and Activate
1. Upload the `zshield-sentinel` folder to `/wp-content/plugins/`.
2. Activate **ZShield Sentinel Security** from **Plugins**.

## 2) Configure Security Settings
Go to **Admin Menu > ZShield** and enable the features you need:
- Login Guard
- Two-Factor Authentication (TOTP)
- Login Captcha (Math or reCAPTCHA)
- Login URL Hardening (custom slug / access key)
- Firewall
- XML-RPC Disable
- Hide WordPress Version
- Disable File Editor
- Security Headers
- Disable Pingbacks/Trackbacks
- Block Author Enumeration
- Audit Log
- File Integrity
- Core File Integrity (optional)
- Malware Scan (scheduled + reports)
- Settings Import/Export
- Role-based Access Control

Click **Save Changes**.

## 3) Create File Integrity Baseline
1. Ensure your site is clean and updated.
2. Click **Create Baseline**.

This stores a safe snapshot of your plugins/themes.

## 4) Run a Manual Scan
Click **Run Scan** anytime to check changes.

## 4.1) View Scan Reports
Go to **ZShield > File Integrity**. You can:
- Open the latest report
- Use **View Report** from Scan History for older scans

## 4.2) Filter and Search Results
Use the **Filter** dropdown to show Added/Modified/Removed files and the **Search** box to quickly find a file path.

## 4.3) Diff Preview (Modified Files)
For modified files, click **Diff Preview** to compare the baseline vs current version.
Note: Diff is available only for files that were stored in the baseline (small files).

## 5) Enable Scheduled Scans (Optional)
1. Turn on **Scheduled Scan**.
2. Choose **Daily** or **Weekly**.
3. Enable **Scan Email Reports** and set an email address.

## 6) Enable Scheduled Malware Scans (Optional)
1. Turn on **Scheduled Malware Scan**.
2. Choose **Daily** or **Weekly**.
3. Enable **Malware Email Reports** and set an email address.

## 7) Monitor the Audit Log
Go to **ZShield > Audit Log** to review:
- Failed logins
- Lockouts
- Successful logins
- Settings updates
- File scan events
- Firewall blocks

## 8) Export Audit Logs (Optional)
Use the **Export CSV** button in **Audit Log** to download recent activity.

## 9) Email Alerts for Lockouts (Optional)
Enable **Email Alerts** and set the **Alert Email**.

## 10) Malware Quarantine (Optional)
From **Malware Scan**, use **Quarantine** on suspicious files. Restore if confirmed safe.

## 11) Settings Import/Export
Use **Settings > Import/Export** to save or load a JSON configuration.

## Best Practices
- Use strong admin passwords and 2FA if possible.
- Keep WordPress, themes, and plugins updated.
- Review audit log weekly.

## Troubleshooting
- If scans show many changes, confirm plugin updates were performed.
- If emails are not received, check SMTP settings.
- If login page is blocked, use the custom login URL or access key from Settings.

