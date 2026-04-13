=== ZShield Sentinel Security ===
Contributors: swastikinfotech
Tags: security, login, audit log, file integrity, hardening
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight security hardening, login guard, 2FA, firewall, malware scanning, and file integrity checks.

== Description ==
ZShield Sentinel adds practical, low-friction security features for WordPress admins:

- Login attempt throttling
- Login IP allowlist/blocklist
- Two-factor authentication (TOTP)
- Login captcha (math or reCAPTCHA)
- Login URL hardening (custom slug or access key)
- XML-RPC disable toggle
- Hide WP version
- Admin editor hide
- Security headers
- Disable pingbacks/trackbacks
- Block author enumeration
- Application firewall for common exploit patterns
- Email alerts on lockout
- Audit log
- Audit log CSV export
- File integrity baseline + scan
- Optional WordPress core integrity scan
- Scheduled scans (daily/weekly)
- Email scan reports
- View report history with detailed file list
- Filter/search scan results and view diff preview for modified files
- Malware scan with allowlists and optional uploads/MU-plugins coverage
- Scheduled malware scans with email reports
- Quarantine and restore suspicious files
- Settings import/export (JSON)
- Role-based access control for plugin pages

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins menu.
3. Go to **ZShield** in the admin menu and configure settings.

== Frequently Asked Questions ==
= Does this replace a full security suite? =
No. It focuses on core hardening features and audit visibility.

== Changelog ==
= 1.1.0 =
- Added 2FA (TOTP) and login captcha support.
- Added login URL hardening with access key or custom slug.
- Added application firewall with allowlist/blocklist controls.
- Added scheduled malware scans with email reports.
- Added quarantine and restore tools for suspicious files.
- Added settings import/export (JSON) and audit log CSV export.
- Added role-based access control and optional core integrity scan.

= 1.0.0 =
- Initial release.

