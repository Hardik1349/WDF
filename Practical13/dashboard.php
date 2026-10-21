<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
requireLogin($conn);
if (($_SESSION['role'] ?? 'student') === 'admin') { header('Location: admin_dashboard.php'); exit; }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Dashboard</title><link rel="stylesheet" href="CSS/style.css"><style>.panel{max-width:760px;margin:45px auto;padding:28px;background:#fff;border-radius:14px;box-shadow:0 8px 25px #0001}.panel h1{color:#4d36a8}.btn{display:inline-block;padding:10px 16px;background:#5b3fd3;color:#fff;border-radius:7px;text-decoration:none}</style></head><body><main class="panel"><h1>Student Dashboard</h1><p>Welcome, <strong><?= e((string)$_SESSION['username']) ?></strong>!</p><p>You are logged in as a <strong>student</strong>. This page is protected by PHP sessions.</p><p>Your session automatically expires after 15 minutes without activity.</p><a class="btn" href="logout.php">Log Out</a></main></body></html>
