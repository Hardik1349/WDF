<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

function finish(string $heading, string $message, bool $success): never {
    $heading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $class = $success ? 'success' : 'failure';
    http_response_code($success ? 200 : 400);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $heading . '</title><link rel="stylesheet" href="CSS/style.css">';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f2ff}.message{max-width:600px;margin:70px auto;padding:30px;background:white;border-radius:12px;box-shadow:0 8px 25px #0001}.success h1{color:#16803c}.failure h1{color:#b42318}.message a{display:inline-block;margin-top:15px;color:#5b3fd3}</style></head><body>';
    echo '<main class="message ' . $class . '"><h1>' . $heading . '</h1><p>' . $message . '</p><a href="secure_registration.html">Back to registration</a></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish('Invalid Request', 'Please submit the registration form.', false);
}

$username = trim((string)($_POST['username'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

$errors = [];
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    $errors[] = 'Username must contain 3–50 letters, numbers or underscores.';
}
if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{8,}$/', $password)) {
    $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, a number and a special character.';
}
if ($password !== $confirmPassword) {
    $errors[] = 'The passwords do not match.';
}
if ($errors) {
    finish('Registration Error', implode(' ', $errors), false);
}

try {
    // Prepared statement prevents user input from becoming SQL code.
    $check = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->bind_param('ss', $username, $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        finish('Registration Error', 'That username or email address is already registered. Please choose another.', false);
    }
    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $insert->bind_param('sss', $username, $email, $passwordHash);
    $insert->execute();
    $insert->close();

    finish('Registration Successful', 'Your account has been created securely. Your password was stored as a hash, not plain text.', true);
} catch (mysqli_sql_exception $e) {
    // A unique database constraint is the final protection against duplicate entries.
    if ((int)$e->getCode() === 1062) {
        finish('Registration Error', 'That username or email address is already registered. Please choose another.', false);
    }
    error_log('Practical 9 registration database error: ' . $e->getMessage());
    finish('Registration Error', 'We could not create your account right now. Check that MySQL is running and the Practical 9 users table has been created.', false);
}
