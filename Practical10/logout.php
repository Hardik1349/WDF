<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
startSecureSession();
if (!empty($_COOKIE['studenthub_remember'])) {
    $parts = explode(':', (string)$_COOKIE['studenthub_remember'], 2);
    if (count($parts) === 2 && preg_match('/^[a-f0-9]{18}$/', $parts[0])) {
        $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $stmt->bind_param('s', $parts[0]); $stmt->execute(); $stmt->close();
    }
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
clearRememberCookie();
header('Location: login.php?loggedout=1'); exit;
