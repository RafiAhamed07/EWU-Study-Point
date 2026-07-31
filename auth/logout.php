<?php
// auth/logout.php
// Destroy the session completely and redirect to the login page.
require_once '../config/db.php';

$_SESSION = [];
session_unset();
session_destroy();

// Also clear the session cookie itself, not just server-side data
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

header('Location: login.php');
exit;