<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
startSecureSession();
$identity = trim((string)($_POST['identity'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($identity === '' || $password === '') { header('Location: login.php?error=1'); exit; }
try {
    $stmt = $conn->prepare('SELECT user_id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $identity, $identity);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        header('Location: login.php?error=1'); exit;
    }
    // Prevent session fixation: assign a fresh session ID after successful authentication.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?: 'student';
    $_SESSION['last_activity'] = time();
    if (isset($_POST['remember']) && $_POST['remember'] === '1') createRememberToken($conn, (int)$user['user_id']);
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
} catch (mysqli_sql_exception $ex) {
    error_log('Practical 10 login error: ' . $ex->getMessage());
    http_response_code(500);
    exit('Login could not be completed. Check that the Practical 9 users table and Practical 10 database changes are set up.');
}
