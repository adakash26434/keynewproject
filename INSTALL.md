# Personal Key Wallet — cPanel Deployment

## Requirements
- PHP 8.x (7.4+ compatible)
- PDO SQLite extension (enabled by default on most hosts)
- OpenSSL extension (enabled by default)
- mbstring extension (enabled by default)

## Database Setup (Important)
- No manual database setup is required.
- The app automatically creates `data/wallet.db` and all required tables on first request.
- You only need correct write permission on `data/`.

## Deployment Steps

### Option A (Recommended): Use repo `public_html/` as web root
This is more secure because `src/` and `data/` stay outside direct web access.

1. Upload full project folder (keep structure as-is):
	- `public_html/`
	- `src/`
	- `data/`
2. Point your domain/subdomain document root to the project's `public_html/` folder.
3. Make `data/` writable by PHP: `chmod 700 data/`
4. Visit your domain.

### Option B: Direct upload into hosting `public_html/`
Use this if your host does not allow custom document root.

1. Upload project files directly inside your hosting `public_html/`.
2. Keep `.htaccess`, `router.php`, `src/`, and `data/` together.
3. Make `data/` writable by PHP: `chmod 700 data/`
4. Visit your domain.

### Option C: Subfolder deployment (e.g. `/wallet`)
1. Upload project to `public_html/wallet/`.
2. Make `public_html/wallet/data/` writable: `chmod 700 public_html/wallet/data/`
3. Visit `yourdomain.com/wallet/`.

## .htaccess (Apache URL Rewriting)
- If using Option A (recommended), use `public_html/.htaccess`.
- If using Option B/C (flat deploy), use project root `.htaccess`.
Both are included in this repository.

## Environment Variables (Optional)
Create a `.env` file in the root with:
```
ENCRYPTION_KEY=your64hexcharskeyhere0000000000000000000000000000000000000000
SESSION_SECRET=anyrandomsecretstring
BREACH_CHECK_ENABLED=false
ALERT_WEBHOOK_URL=
ALERT_WEBHOOK_MODE=generic
```
If not set, the app auto-generates and stores an encryption key in `data/.encryption_key`.

### Breach Monitoring (Optional)
- Set `BREACH_CHECK_ENABLED=true` to enable password breach checks in Insights.
- Uses k-anonymity (`/range/{prefix}`) so full password hash is never sent.
- Network/API failures are handled gracefully and cached.

### Smart Alert Webhook (Optional)
- Set `ALERT_WEBHOOK_URL` to receive daily smart alert digest (JSON POST).
- Example payload keys: `title`, `message`, `payload.user_id`, `payload.count`.
- Set `ALERT_WEBHOOK_MODE=telegram` for Telegram Bot API style `text` payload.

## MySQL Mode (Practical)
If you want MySQL (recommended for higher write concurrency), set these in `.env`:
```
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=key_wallet
DB_USER=your_mysql_user
DB_PASS=your_mysql_password
DB_CHARSET=utf8mb4
```

Notes:
1. Enable `pdo_mysql` extension in PHP.
2. Create database first (example): `CREATE DATABASE key_wallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Tables are auto-created by the app on first run, same as SQLite mode.
4. Existing SQLite data is not auto-copied. Export/import via Profile backup is the safest migration path.

## First Run
1. Visit the app URL
2. Click "Create vault" to register
3. Scan the QR code with Google Authenticator
4. Enter the 6-digit code to enable 2FA
5. You're in!

## CSV Import
- Password Vault supports CSV import from:
	1. Google Chrome export
	2. Bitwarden export
- Use Passwords page -> `Preview CSV` -> review duplicates/rows -> `Confirm Import`.
- Imported passwords are encrypted at rest immediately.

## PWA Install
- App includes PWA manifest + service worker.
- On supported browsers, click `Install App` button in the top bar.

## Quick Troubleshooting
- If you see a 500 setup error, check:
	1. `pdo_sqlite` extension is enabled.
	2. `openssl` extension is enabled.
	3. `data/` is writable (`chmod 700 data`).
- If you see 403, confirm your subdomain document root points to the correct folder and `.htaccess` is present.

## High-Traffic Notes
- This version includes query indexes, pagination, and SQLite tuning for better concurrency.
- Keep PHP OPcache enabled for faster response times.
- Keep HTTPS enabled and avoid heavy debug logging on production.
- Recommended maintenance (weekly on busy servers):
	1. Backup `data/wallet.db`.
	2. Run `VACUUM;` and `ANALYZE;` on SQLite during low-traffic period.
- For very high concurrent write traffic, use MySQL mode in this app.
