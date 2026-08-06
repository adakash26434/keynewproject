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
$router->post('/passwords/{id}/update', [PasswordController::class, 'update']);
$router->post('/passwords/{id}/delete', [PasswordController::class, 'delete']);
$router->get('/passwords/{id}/reveal',  [PasswordController::class, 'reveal']);

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
