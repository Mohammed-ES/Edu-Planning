<?php
/**
 * Authentication Helper Functions
 * 
 * Common security and authentication utilities for the Edu-Planning application.
 */

/**
 * Check if user is authenticated
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isUserAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true 
        && isset($_SESSION['user_id']) 
        && isset($_SESSION['email']);
}

/**
 * Get the current authenticated user's information
 * 
 * @return array|null User information array or null if not authenticated
 */
function getCurrentUser() {
    if (!isUserAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'name' => $_SESSION['name'] ?? null,
        'avatar' => $_SESSION['avatar'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
    ];
}

/**
 * Require authentication - redirect to login if not authenticated
 * 
 * @param string $redirectTo Page to redirect to after login (optional)
 * @return void
 */
function requireAuth($redirectTo = null) {
    if (!isUserAuthenticated()) {
        if ($redirectTo) {
            $_SESSION['redirect_after_login'] = $redirectTo;
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * Logout user and destroy session
 * 
 * @param PDO $pdo Database connection (optional, for logging)
 * @return void
 */
function logout($pdo = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Log logout action
    if ($pdo && isset($_SESSION['user_id'])) {
        try {
            logAction($_SESSION['user_id'], 'logout', $pdo);
        } catch (Exception $e) {
            error_log('Logout logging error: ' . $e->getMessage());
        }
    }
    
    // Clear session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Validate session security
 * 
 * @param int $maxAge Maximum session age in seconds (default: 24 hours)
 * @return bool True if session is valid, false otherwise
 */
function validateSessionSecurity($maxAge = 86400) {
    if (!isUserAuthenticated()) {
        return false;
    }
    
    if (!isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check if session has expired
    $sessionAge = time() - $_SESSION['login_time'];
    if ($sessionAge > $maxAge) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize user input
 * 
 * @param string $input User input
 * @param string $type Type of sanitization (email, string, url, int)
 * @return string|int Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'string':
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Hash a password (for future use if direct password storage is needed)
 * 
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 * 
 * @param string $password Plain password
 * @param string $hash Password hash
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a secure CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
