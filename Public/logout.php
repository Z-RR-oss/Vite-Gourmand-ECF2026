<?php

require_once __DIR__ . '/../Config/bootstrap.php';
requirePost();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
}
session_destroy();
header('Location: login.php', true, 303);
exit;
