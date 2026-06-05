# Production Update Notes

Updated on: 2026-05-11

## Backup

A local backup was created before the production-safe updates:

`C:\Users\HP\Desktop\MLMP\_backup_before_production_update_20260511-152541`

## What Was Updated

- Added password hashing with backward-compatible migration for existing plaintext passwords.
- Hardened protected pages so redirects stop execution immediately.
- Added safer admin action handling for selected user/package/payment actions.
- Made profile password changes optional so users/admins do not need to re-enter passwords on every profile edit.
- Hid stored password hashes from the admin user edit screen.
- Added a 2026 CSS-only UI refresh for user and admin areas.
- Added missing admin `premium.css`.
- Added `.htaccess` protection for sensitive helper/maintenance files.
- Updated old visible admin footer year to use the current year.
- Continued admin action hardening by using server-side login redirects on updated admin action endpoints and escaping the renewal launch link username.
- Migrated `admin/backups/backup.php` from deprecated `mysql_*` APIs to `mysqli` and fixed its admin DB include path for PHP 8 compatibility.
- Fixed `dashboard.php` null-handling for "Last Referral" widget to prevent SQL fatal errors for users with no referrals yet.
- Added and executed an end-to-end local smoke script (`run_full_flow_smoke.ps1`) covering admin login, signup, activation, user login, protected pages, and key admin actions (`FLOW_STATUS=PASS`).

## Important Production Checks

- Confirm Apache allows `.htaccess` overrides for the deployed folder.
- Confirm the live database credentials in `z_db.php` and `admin/z_db.php`.
- Test admin login, user login, signup, profile update, package creation, package update, and payment approval after upload.
- PHP 8.2 CLI linting was run successfully across `User/**/*.php` with zero syntax errors.
- Keep `updatesql.php`, `restore_footer.php`, and direct config/helper files inaccessible from the browser.
- Local signup flow may still emit a non-fatal mail warning if no SMTP server is configured on `localhost:25`.

## Local Test URL

`http://127.0.0.1:8000`
