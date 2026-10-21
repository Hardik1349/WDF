<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
requireLogin($conn);
if (($_SESSION['role'] ?? 'student') !== 'admin') { header('Location: dashboard.php?access=denied'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = (string)$_SESSION['csrf_token'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Practical 13 - Events JSON API</title>
<link rel="stylesheet" href="CSS/style.css">
<style>
.api-wrap{max-width:1100px;margin:28px auto;padding:18px}.api-panel{background:#fff;padding:22px;border-radius:14px;box-shadow:0 5px 20px #0001;margin-bottom:20px}.api-panel h1,.api-panel h2{color:#4d36a8}.api-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.api-form label{display:flex;flex-direction:column;gap:6px;font-weight:600}.api-form input,.api-form textarea,.api-form select{padding:10px;border:1px solid #ccc;border-radius:7px;font:inherit;width:100%;box-sizing:border-box}.api-form textarea{min-height:85px;resize:vertical}.wide{grid-column:1/-1}.api-btn{border:0;border-radius:7px;padding:10px 15px;cursor:pointer;background:#5b3fd3;color:#fff;font-weight:600;margin:3px}.api-btn.secondary{background:#64748b}.api-btn.danger{background:#c0392b}.api-btn.small{padding:7px 10px}.api-message{padding:11px;border-radius:7px;margin:12px 0;display:none}.api-message.ok{display:block;background:#e8f7ed;color:#176534}.api-message.err{display:block;background:#fdecec;color:#922b21}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;background:#fff}th,td{text-align:left;padding:11px;border-bottom:1px solid #e5e7eb;vertical-align:top}th{background:#f4f1ff}.muted{color:#666;font-size:.93rem}.empty{text-align:center;padding:22px;color:#666}
@media(max-width:650px){.api-form{grid-template-columns:1fr}.wide{grid-column:auto}.api-wrap{padding:10px}.api-panel{padding:15px}}
</style>
</head>
<body>
<main class="api-wrap" data-csrf="<?= e($csrfToken) ?>">
<section class="api-panel">
<h1>Practical 13: Events JSON API</h1>
<p>Manage events using PHP JSON API endpoints and the JavaScript Fetch API. Add, view, update and delete actions happen without reloading this page.</p>
<p class="muted">Admin access required · API endpoint: <code>api/events.php</code></p>
<div id="apiMessage" class="api-message" role="status" aria-live="polite"></div>
<form id="eventForm" class="api-form">
<input type="hidden" id="eventId" value="">
<label>Event title *<input id="title" maxlength="150" required></label>
<label>Event date and time *<input id="eventDate" type="datetime-local" required></label>
<label>Venue *<input id="venue" maxlength="150" required></label>
<label>Category *<input id="category" maxlength="60" required placeholder="Workshop, Competition..."></label>
<label class="wide">Description<textarea id="description" maxlength="5000"></textarea></label>
<div class="wide"><button class="api-btn" id="saveButton" type="submit">Add Event</button><button class="api-btn secondary" id="cancelButton" type="button" hidden>Cancel Edit</button><button class="api-btn secondary" id="refreshButton" type="button">Refresh List</button></div>
</form>
</section>
<section class="api-panel">
<h2>Event List</h2>
<p class="muted" id="eventCount">Loading events...</p>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Event</th><th>Date &amp; Time</th><th>Venue</th><th>Category</th><th>Actions</th></tr></thead><tbody id="eventsBody"><tr><td colspan="6" class="empty">Loading...</td></tr></tbody></table></div>
</section>
<p><a href="admin_dashboard.php">← Back to Admin Dashboard</a></p>
</main>
<script src="js/events_api.js" defer></script>
</body></html>
