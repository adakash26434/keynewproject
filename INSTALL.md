# Personal Key Wallet — cPanel Deployment

## Requirements
- PHP 8.x (7.4+ compatible)
- PDO SQLite extension (enabled by default on most hosts)
- OpenSSL extension (enabled by default)
- mbstring extension (enabled by default)

## Deployment Steps

### Option A: Root Domain / Subdomain
1. Upload all files to `public_html/`
2. The `data/` folder must be writable by PHP: `chmod 700 data/`
3. Visit your domain — the app will auto-create the SQLite database

### Option B: Subfolder (e.g. /wallet)
1. Upload all files to `public_html/wallet/`
2. Make `data/` writable: `chmod 700 data/`
3. Visit `yourdomain.com/wallet/`

## .htaccess (Apache URL Rewriting)
The included `.htaccess` handles URL routing automatically.
No extra configuration needed on Apache/LiteSpeed servers.

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
