<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    log_activity((int) $_SESSION['user_id'], 'logout', 'Signed out');
}


$_SESSION = array();


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


if (session_id() !== '') {
    @session_destroy();
}


prevent_browser_cache();


session_start();
session_regenerate_id(true);
flash_set('ok', 'You have been successfully logged out. Please log in again to continue.');

redirect('/login.php');

