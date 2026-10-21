<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_helpers.php';
startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['role'] ?? 'student') === 'admin' ? 'admin_dashboard.php' : 'dashboard.php')); exit;
}
$message = '';
if (isset($_GET['timeout'])) $message = 'Your session expired after 15 minutes of inactivity. Please log in again.';
elseif (isset($_GET['required'])) $message = 'Please log in to view the dashboard.';
elseif (isset($_GET['loggedout'])) $message = 'You have been logged out successfully.';
elseif (isset($_GET['registered'])) $message = 'Registration completed. You can now log in.';
elseif (isset($_GET['error'])) $message = 'Invalid username/email or password.';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub Login</title><link rel="stylesheet" href="CSS/style.css"><style>
.auth-card{max-width:460px;margin:55px auto;padding:28px;background:#fff;border-radius:14px;box-shadow:0 8px 25px #0001}.auth-card h1{margin-top:0;color:#4d36a8}.auth-card label{display:block;margin:14px 0 6px;font-weight:600}.auth-card input[type=text],.auth-card input[type=password]{width:100%;padding:11px;border:1px solid #bbb;border-radius:7px;box-sizing:border-box}.auth-card button{margin-top:18px;padding:11px 18px;background:#5b3fd3;color:white;border:0;border-radius:7px;cursor:pointer}.notice{background:#f1edff;padding:11px;border-radius:7px}.remember{display:flex;align-items:center;gap:8px;margin-top:14px}.small{font-size:.9rem;color:#555}
</style></head><body><main class="auth-card"><h1>StudentHub Login</h1><p>Log in with the username or email you registered in Practical 9.</p>
<?php if ($message !== ''): ?><p class="notice"><?= e($message) ?></p><?php endif; ?>
<form action="login_process.php" method="post" autocomplete="on">
<label for="identity">Username or Email</label><input id="identity" name="identity" type="text" maxlength="150" required autocomplete="username">
<label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password">
<label class="remember"><input type="checkbox" name="remember" value="1"> Remember me for up to 30 days on this device</label>
<button type="submit">Log In</button></form><p class="small">No account yet? <a href="secure_registration.html">Register here</a>.</p><p><a href="index.html">Back to Home</a></p></main></body></html>
