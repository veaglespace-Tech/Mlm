# MLM Platform (Binary & Unilevel Affiliate System)

A modern PHP 8+ & MySQL affiliate/MLM management platform featuring Binary + Unilevel tree tracking, automated pair capping & countdown qualification, PayU payment integration, KYC verification, support ticketing, and an Admin 2FA OTP login flow.

---

## Project Structure

```text
MLMP/
├── .env                    # Environment secrets (local/server only, gitignored)
├── .env.example            # Template for environment variables
├── .htaccess               # Blocks direct web access to .env, .sql, .log, and dotfiles
├── index.php               # Root entrypoint redirecting to User/index.php
├── database/
│   └── schema.sql          # Base database installation SQL
├── scripts/                # CLI/Admin dev utilities (blocked from web access)
└── User/                   # Web-facing application root (deployed via CI/CD)
    ├── .htaccess           # Hardens internal PHP helpers & security headers
    ├── index.php           # Unified User Login + Admin 2FA OTP flow
    ├── dashboard.php       # Member dashboard
    ├── z_db.php            # PDO database bootstrap, MLM binary core, & helpers
    ├── csrf_helper.php     # Secure session cookie config & CSRF token guards
    ├── smtp_helper.php     # Pure-PHP TLS/SSL SMTP mailer
    ├── css/ & js/ & images/# Frontend static assets
    └── admin/              # Admin command center (session + level=1 guarded)
        └── backups/        # Database backup generator (protected by .htaccess)
```

---

## Quick Local Setup

1. **Database Import:**
   Create a MySQL database (e.g. `mlmp`) and import `database/schema.sql`.
2. **Environment Configuration:**
   Copy `.env.example` to `.env` and fill in your DB, SMTP, and PayU credentials:
   ```bash
   cp .env.example .env
   ```
3. **Run Local Server:**
   ```bash
   php -S 127.0.0.1:8000
   ```
4. **Access Portals:**
   - **Member & Admin Login:** `http://127.0.0.1:8000/User/index.php`
   - **Admin Direct Portal:** `http://127.0.0.1:8000/User/admin/`

---

## Security Highlights

- **Prepared Statements Only:** All database queries across User & Admin portals use PDO parameterized statements.
- **CSRF Protection:** Token generation and validation (`csrf_helper.php`) across state-changing forms.
- **Hardened Sessions:** `HttpOnly`, `SameSite=Lax`, `use_only_cookies`, and `session_regenerate_id(true)` on login.
- **Admin 2FA OTP:** Level-1 administrators verify a 6-digit email OTP before entering the admin workspace.
- **Access Controls:** `.htaccess` files protect `.env`, `database/`, `scripts/`, `User/admin/backups/`, and internal helpers.
