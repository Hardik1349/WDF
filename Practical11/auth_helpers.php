<?php
declare(strict_types=1);

// Practical 10: session security helpers.
const SESSION_TIMEOUT = 900; // 15 minutes, in seconds

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https, // true on HTTPS; false for local XAMPP HTTP
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function clearRememberCookie(): void {
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('studenthub_remember', '', [
        'expires' => time() - 3600, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
}

function createRememberToken(mysqli $conn, int $userId): void {
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expires = time() + (30 * 24 * 60 * 60);
    $expiresAt = date('Y-m-d H:i:s', $expires);
    $stmt = $conn->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $selector, $validatorHash, $expiresAt);
    $stmt->execute();
    $stmt->close();
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('studenthub_remember', $selector . ':' . $validator, [
        'expires' => $expires, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
}

function tryRememberLogin(mysqli $conn): void {
    if (!empty($_SESSION['user_id']) || empty($_COOKIE['studenthub_remember'])) return;
    $parts = explode(':', (string)$_COOKIE['studenthub_remember'], 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{18}$/', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
        clearRememberCookie(); return;
    }
    [$selector, $validator] = $parts;
    $stmt = $conn->prepare('SELECT rt.user_id, rt.validator_hash, rt.expires_at, u.username, u.role FROM remember_tokens rt JOIN users u ON u.user_id = rt.user_id WHERE rt.selector = ? LIMIT 1');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row || strtotime($row['expires_at']) < time() || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        $delete = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
        $delete->bind_param('s', $selector); $delete->execute(); $delete->close();
        clearRememberCookie(); return;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$row['user_id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['last_activity'] = time();
    // Rotate the remember token after use so a copied token cannot be reused indefinitely.
    $delete = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
    $delete->bind_param('s', $selector); $delete->execute(); $delete->close();
    clearRememberCookie();
    createRememberToken($conn, (int)$row['user_id']);
}

function requireLogin(mysqli $conn): void {
    startSecureSession();
    tryRememberLogin($conn);
    if (!empty($_SESSION['user_id'])) {
        $last = (int)($_SESSION['last_activity'] ?? time());
        if (time() - $last > SESSION_TIMEOUT) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
            }
            session_destroy();
            header('Location: login.php?timeout=1'); exit;
        }
        $_SESSION['last_activity'] = time();
        return;
    }
    header('Location: login.php?required=1'); exit;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
