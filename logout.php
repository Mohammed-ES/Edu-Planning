<?php
require_once __DIR__ . '/include/auth.php';

if (is_logged_in()) {
    logAction($_SESSION['user_id'], 'logout', $pdo);
}

$_SESSION = [];
session_destroy();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: login.php");
exit;
?>
