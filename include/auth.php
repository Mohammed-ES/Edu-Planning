<?php
/**
 * auth.php — Authentication helpers & session management
 *
 * Provides: session startup, CSRF token, login guards,
 * brute-force protection, and audit logging.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// --------------------------------------------------------------------------
// CSRF TOKEN
// --------------------------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --------------------------------------------------------------------------
// LOGIN STATE
// --------------------------------------------------------------------------
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /Edu-Planning-v1/login.php');
        exit();
    }
}

// --------------------------------------------------------------------------
// CURRENT USER
// --------------------------------------------------------------------------
function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

// --------------------------------------------------------------------------
// BRUTE-FORCE PROTECTION — Session-based rate limiting
// Allows MAX_LOGIN_ATTEMPTS per LOCKOUT_WINDOW seconds per IP.
// --------------------------------------------------------------------------
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_WINDOW', 900); // 15 minutes

function check_login_rate_limit(): bool
{
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data    = &$_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];

    // Reset window if enough time has passed
    if ($elapsed >= LOCKOUT_WINDOW) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    return $data['count'] < MAX_LOGIN_ATTEMPTS;
}

function increment_login_attempt(): void
{
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    $_SESSION[$key]['count']++;
}

function reset_login_attempts(): void
{
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

function get_remaining_lockout_seconds(): int
{
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    return max(0, LOCKOUT_WINDOW - (int)$elapsed);
}

// --------------------------------------------------------------------------
// AUDIT LOG — writes to error_log (extend to DB table when needed)
// --------------------------------------------------------------------------
function logAction(?int $user_id, string $action, PDO $pdo): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    error_log(sprintf(
        '[EDU-PLANNING] action=%s user_id=%s ip=%s',
        $action,
        $user_id ?? 'guest',
        $ip
    ));
    return true;
}
