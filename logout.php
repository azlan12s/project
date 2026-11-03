<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    log_activity((int) $_SESSION['user_id'], 'logout', 'Signed out');
}
$_SESSION = array();
if (session_id() !== '') {
    session_destroy();
}
redirect('/index.php');
