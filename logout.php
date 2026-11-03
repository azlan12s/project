<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    log_activity((int) $_SESSION['user_id'], 'logout', 'Signed out');
}

// Clear all session variables
$_SESSION = array();

// Invalidate session cookie in browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the current session
if (session_id() !== '') {
    @session_destroy();
}

// Ensure logout response is never cached by browser
prevent_browser_cache();

// Start a clean session to deliver the logout confirmation flash notice
session_start();
session_regenerate_id(true);
flash_set('ok', 'You have been successfully logged out. Please log in again to continue.');

redirect('/login.php');
