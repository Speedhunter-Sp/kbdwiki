<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie: HttpOnly stops JS from reading it, SameSite=Lax
    // stops it being sent on cross-site form posts, Secure requires HTTPS
    // whenever the request actually arrived over HTTPS (e.g. once you're on
    // Hostinger behind SSL).
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

// ---------- CSRF protection for the admin add/edit/delete forms ----------

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

// Returns true if the submitted token matches. Call this at the top of any
// POST handler that changes data.
function csrf_valid(): bool {
    $submitted = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $submitted);
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function current_username(): ?string {
    return $_SESSION['username'] ?? null;
}

function admin_account_exists(): bool {
    $stmt = get_db()->query('SELECT COUNT(*) AS c FROM users');
    return (int)$stmt->fetch()['c'] > 0;
}

// Resolve a project-relative path (e.g. "login.php" or "admin/add.php")
// to a working URL regardless of which subfolder the current script lives in.
function base_url(string $path = ''): string {
    $root = dirname($_SERVER['SCRIPT_NAME']);
    // If we're inside /admin/, step back up one level.
    if (basename($root) === 'admin') {
        $root = dirname($root);
    }
    // When the site lives at the domain root, $root ends up as '/' —
    // rtrim it down to '' so the line below never produces '//path'
    // (which browsers read as "new hostname", not "root path").
    $root = rtrim($root, '/');
    return $root . '/' . ltrim($path, '/');
}