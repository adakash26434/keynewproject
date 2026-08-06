# Browser Extension MVP

This is a basic extension MVP for quick access to Aakash Key Vault.

## Features
- Save your vault base URL.
- One-click open Dashboard and Passwords.
- Quick vault credential search.
- Domain-aware result ranking for active tab.
- Autofill selected credential into active tab.
- Built-in strong password generator.

## Load in Chrome/Edge (Developer Mode)
1. Open extensions page (`chrome://extensions` or `edge://extensions`).
2. Enable **Developer mode**.
3. Click **Load unpacked**.
4. Select this folder: `browser-extension-mvp/`.

## Notes
- You must be logged in on your vault domain for search/autofill API calls.
- Autofill targets visible login-like username/email and password inputs.
