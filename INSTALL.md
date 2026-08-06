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
```
If not set, the app auto-generates and stores an encryption key in `data/.encryption_key`.

## First Run
1. Visit the app URL
2. Click "Create vault" to register
3. Scan the QR code with Google Authenticator
4. Enter the 6-digit code to enable 2FA
5. You're in!

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
- For very high concurrent write traffic, plan migration to MySQL/PostgreSQL.
