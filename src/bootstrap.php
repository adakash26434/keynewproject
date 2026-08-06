<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Base32.php';
require_once __DIR__ . '/Crypto.php';
require_once __DIR__ . '/Totp.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Router.php';

// Load controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/PasswordController.php';
require_once __DIR__ . '/controllers/DocumentController.php';
require_once __DIR__ . '/controllers/FinanceController.php';
require_once __DIR__ . '/controllers/TaskController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/CVController.php';
require_once __DIR__ . '/controllers/InsightsController.php';
require_once __DIR__ . '/controllers/SearchController.php';

// Start session
Auth::start();

// Boot database (creates tables if needed)
Database::getInstance();

// Register routes
$router = new Router();

// ── Auth routes ─────────────────────────────────────────────────────────────
$router->get('/',           fn() => Auth::check() ? redirect('/dashboard') : redirect('/login'));
$router->get('/login',      [AuthController::class, 'showLogin']);
$router->post('/login',     [AuthController::class, 'handleLogin']);
$router->get('/signup',     [AuthController::class, 'showSignup']);
$router->post('/signup',    [AuthController::class, 'handleSignup']);
$router->get('/setup-2fa',  [AuthController::class, 'showSetup2fa']);
$router->post('/setup-2fa', [AuthController::class, 'handleSetup2fa']);
$router->get('/verify-2fa', [AuthController::class, 'showVerify2fa']);
$router->post('/verify-2fa',[AuthController::class, 'handleVerify2fa']);
$router->post('/logout',    [AuthController::class, 'logout']);
$router->post('/logout-all',[AuthController::class, 'logoutAll']);
$router->get('/logout',     fn() => redirect('/dashboard'));

// ── Dashboard & Insights ─────────────────────────────────────────────────────
$router->get('/dashboard',  [DashboardController::class, 'index']);
$router->get('/insights',   [InsightsController::class,  'index']);

// ── Vault: Passwords ─────────────────────────────────────────────────────────
$router->get('/passwords',              [PasswordController::class, 'index']);
$router->post('/passwords',             [PasswordController::class, 'store']);
$router->post('/passwords/import-csv',  [PasswordController::class, 'importCsv']);
$router->post('/passwords/import-csv/confirm', [PasswordController::class, 'confirmImportCsv']);
$router->post('/passwords/import-csv/remove-row', [PasswordController::class, 'removeImportPreviewRow']);
$router->post('/passwords/import-csv/clear', [PasswordController::class, 'clearImportPreview']);
$router->post('/passwords/{id}/update', [PasswordController::class, 'update']);
$router->post('/passwords/{id}/delete', [PasswordController::class, 'delete']);
$router->get('/passwords/{id}/reveal',  [PasswordController::class, 'reveal']);
$router->get('/api/passwords/search',   [PasswordController::class, 'extensionSearch']);

// ── Vault: Documents ─────────────────────────────────────────────────────────
$router->get('/documents',              [DocumentController::class, 'index']);
$router->post('/documents',             [DocumentController::class, 'store']);
$router->post('/documents/{id}/update', [DocumentController::class, 'update']);
$router->post('/documents/{id}/delete', [DocumentController::class, 'delete']);

// ── Finance ──────────────────────────────────────────────────────────────────
$router->get('/finance',                    [FinanceController::class, 'index']);
$router->get('/finance/analytics',          [FinanceController::class, 'analytics']);
$router->post('/finance',                   [FinanceController::class, 'store']);
$router->post('/finance/{id}/update',       [FinanceController::class, 'update']);
$router->post('/finance/{id}/delete',       [FinanceController::class, 'delete']);

// ── Tasks ─────────────────────────────────────────────────────────────────────
$router->get('/tasks',                  [TaskController::class, 'index']);
$router->post('/tasks',                 [TaskController::class, 'store']);
$router->post('/tasks/{id}/update',     [TaskController::class, 'update']);
$router->post('/tasks/{id}/delete',     [TaskController::class, 'delete']);
$router->post('/tasks/{id}/toggle',     [TaskController::class, 'toggle']);

// ── CV Builder ────────────────────────────────────────────────────────────────
$router->get('/cv',  [CVController::class, 'index']);
$router->post('/cv', [CVController::class, 'save']);

// ── Profile ───────────────────────────────────────────────────────────────────
$router->get('/profile',           [ProfileController::class, 'index']);
$router->post('/profile',          [ProfileController::class, 'update']);
$router->post('/profile/password', [ProfileController::class, 'changePassword']);
$router->post('/profile/sessions/{id}/revoke', [ProfileController::class, 'revokeSession']);
$router->post('/profile/backup/export', [ProfileController::class, 'exportBackup']);
$router->post('/profile/backup/import', [ProfileController::class, 'importBackup']);

// ── Global Search (JSON) ──────────────────────────────────────────────────────
$router->get('/search', [SearchController::class, 'search']);

// ── PWA assets ────────────────────────────────────────────────────────────────
$router->get('/manifest.webmanifest', function () {
    header('Content-Type: application/manifest+json; charset=utf-8');
    echo json_encode([
        'name' => APP_NAME,
        'short_name' => 'KeyWallet',
        'start_url' => '/dashboard',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#f1f5f9',
        'theme_color' => '#0f172a',
        'description' => 'Secure personal key wallet with passwords, documents, tasks and finance insights.',
        'icons' => [
            [
                'src' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"%3E%3Crect width="128" height="128" rx="24" fill="%230078D4"/%3E%3Cpath d="M86 36a12 12 0 0 1 12 12v1h6v16h-13l-8 8h-7v11H60V73H45a17 17 0 1 1 0-34h41Zm0 11H45a6 6 0 1 0 0 12h20v14h8V59h13a6 6 0 0 0 0-12Z" fill="white"/%3E%3C/svg%3E',
                'sizes' => '128x128',
                'type' => 'image/svg+xml',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit;
});

$router->get('/sw.js', function () {
    header('Content-Type: application/javascript; charset=utf-8');
    echo "const CACHE_NAME = 'keywallet-shell-v1';\n"
       . "const URLS = ['/login', '/signup', '/dashboard', '/insights'];\n"
       . "self.addEventListener('install', event => {\n"
       . "  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(URLS)).catch(() => null));\n"
       . "  self.skipWaiting();\n"
       . "});\n"
       . "self.addEventListener('activate', event => {\n"
       . "  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))));\n"
       . "  self.clients.claim();\n"
       . "});\n"
       . "self.addEventListener('fetch', event => {\n"
       . "  if (event.request.method !== 'GET') return;\n"
       . "  event.respondWith(fetch(event.request).catch(() => caches.match(event.request).then(r => r || caches.match('/dashboard'))));\n"
       . "});\n";
    exit;
});

// ── Static pages ──────────────────────────────────────────────────────────────
$router->get('/privacy', function() {
    Auth::requireAuth();
    view('pages/privacy', ['pageTitle' => 'Privacy Policy', 'user' => Auth::user()]);
});
$router->get('/terms', function() {
    Auth::requireAuth();
    view('pages/terms', ['pageTitle' => 'Terms of Service', 'user' => Auth::user()]);
});

$router->run();
