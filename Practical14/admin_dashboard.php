<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
requireLogin($conn);
if (($_SESSION['role'] ?? 'student') !== 'admin') {
    header('Location: dashboard.php?access=denied');
    exit;
}

$userCount = $studentCount = $eventCount = $registrationCount = 0;
$latestRegistrations = [];
$setupWarnings = [];

// Load each statistic separately so a missing table is reported without exposing SQL details.
$statQueries = [
    'users' => 'SELECT COUNT(*) AS total FROM users',
    'students' => 'SELECT COUNT(*) AS total FROM students',
    'events' => 'SELECT COUNT(*) AS total FROM events',
    'registrations' => 'SELECT COUNT(*) AS total FROM registrations'
];
foreach ($statQueries as $key => $sql) {
    try {
        $row = $conn->query($sql)->fetch_assoc();
        ${$key === 'users' ? 'userCount' : ($key === 'students' ? 'studentCount' : ($key === 'events' ? 'eventCount' : 'registrationCount'))} = (int)($row['total'] ?? 0);
    } catch (mysqli_sql_exception $ex) {
        $setupWarnings[] = ucfirst($key) . ' statistics are unavailable. Check the Practical 8 database tables.';
        error_log('Practical 14 dashboard count failed (' . $key . '): ' . $ex->getMessage());
    }
}

try {
    $sql = "SELECT r.registration_id, r.registered_at, r.status,
                   s.full_name, s.email, e.title AS event_title, e.event_date
            FROM registrations r
            INNER JOIN students s ON s.student_id = r.student_id
            INNER JOIN events e ON e.event_id = r.event_id
            ORDER BY r.registered_at DESC, r.registration_id DESC
            LIMIT 8";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) $latestRegistrations[] = $row;
    $result->free();
} catch (mysqli_sql_exception $ex) {
    $setupWarnings[] = 'Latest registrations are unavailable. Check the registrations, students, and events tables.';
    error_log('Practical 14 latest registrations failed: ' . $ex->getMessage());
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard | StudentHub</title>
<link rel="stylesheet" href="CSS/style.css">
<style>
:root{--primary:#5b3fd3;--ink:#20233a;--muted:#687087;--line:#e7e8f0;--surface:#fff;--page:#f5f6fb}
*{box-sizing:border-box}body{margin:0;background:var(--page);color:var(--ink);font-family:Arial,Helvetica,sans-serif}
.topbar{background:#fff;border-bottom:1px solid var(--line);padding:14px 22px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}
.brand{font-weight:800;font-size:1.15rem;color:var(--primary);text-decoration:none}.nav{display:flex;gap:8px;flex-wrap:wrap}.nav a{padding:9px 12px;border-radius:8px;text-decoration:none;color:#45465b;font-size:.94rem}.nav a:hover,.nav a.active{background:#eeeaff;color:var(--primary)}
.wrap{width:min(1120px,94%);margin:30px auto}.welcome{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:22px}.welcome h1{margin:0 0 8px;font-size:clamp(1.6rem,4vw,2.1rem)}.muted{color:var(--muted)}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:22px 0}.stat{background:var(--surface);padding:21px;border-radius:14px;border:1px solid var(--line);box-shadow:0 5px 18px #27264b08}.stat-label{color:var(--muted);font-size:.94rem}.stat-value{font-size:2rem;font-weight:800;margin-top:10px;color:var(--primary)}
.actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:20px 0 26px}.action{display:block;background:#fff;border:1px solid var(--line);padding:17px;border-radius:12px;color:var(--ink);text-decoration:none}.action strong{display:block;color:var(--primary);margin-bottom:6px}.action:hover{border-color:#b7aafa;transform:translateY(-1px)}
.panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;margin-top:20px}.panel h2{font-size:1.2rem;margin:0 0 15px}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:680px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid var(--line);font-size:.93rem}th{background:#f7f6ff;color:#55506e}tbody tr:last-child td{border-bottom:0}.status{display:inline-block;padding:4px 8px;border-radius:20px;background:#eaf7ee;color:#20683a;font-size:.8rem}.warning{padding:12px 14px;border-radius:9px;background:#fff4d7;color:#795600;margin:12px 0}.empty{text-align:center;padding:25px;color:var(--muted)}.logout{background:var(--primary)!important;color:white!important}
@media(max-width:850px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.actions{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.stats,.actions{grid-template-columns:1fr}.topbar{padding:12px}.wrap{margin:20px auto}.stat{padding:17px}.panel{padding:14px}}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand" href="admin_dashboard.php">StudentHub Admin</a>
  <nav class="nav" aria-label="Admin navigation">
    <a class="active" href="admin_dashboard.php">Dashboard</a>
    <a href="students.php">Manage Students</a>
    <a href="events_admin.php">Manage Events</a>
    <a href="events_api.php">Events API</a>
    <a class="logout" href="logout.php">Log Out</a>
  </nav>
</header>
<main class="wrap">
  <section class="welcome">
    <div><h1>Admin Dashboard</h1><p class="muted">Welcome back, <strong><?= e((string)($_SESSION['username'] ?? 'Admin')) ?></strong>. Here is the latest StudentHub overview.</p></div>
    <span class="status">Role: Administrator</span>
  </section>
  <?php foreach (array_unique($setupWarnings) as $warning): ?><div class="warning"><?= e($warning) ?></div><?php endforeach; ?>
  <section class="stats" aria-label="Platform statistics">
    <article class="stat"><div class="stat-label">Registered Users</div><div class="stat-value"><?= number_format($userCount) ?></div></article>
    <article class="stat"><div class="stat-label">Students</div><div class="stat-value"><?= number_format($studentCount) ?></div></article>
    <article class="stat"><div class="stat-label">Events</div><div class="stat-value"><?= number_format($eventCount) ?></div></article>
    <article class="stat"><div class="stat-label">Event Registrations</div><div class="stat-value"><?= number_format($registrationCount) ?></div></article>
  </section>
  <section aria-label="Admin tools">
    <h2>Quick Actions</h2>
    <div class="actions">
      <a class="action" href="students.php"><strong>Manage Students →</strong><span class="muted">View, add, update, search, or delete student records.</span></a>
      <a class="action" href="events_admin.php"><strong>Manage Events →</strong><span class="muted">Create events and manage event details and posters.</span></a>
      <a class="action" href="events_api.php"><strong>Events JSON API →</strong><span class="muted">Test asynchronous event CRUD with Fetch API.</span></a>
    </div>
  </section>
  <section class="panel">
    <h2>Latest Registrations</h2>
    <p class="muted">The 8 most recent event registrations are shown below.</p>
    <div class="table-wrap"><table>
      <thead><tr><th>Registration ID</th><th>Student</th><th>Email</th><th>Event</th><th>Event Date</th><th>Registered At</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$latestRegistrations): ?><tr><td class="empty" colspan="7">No registrations found yet.</td></tr>
      <?php else: foreach ($latestRegistrations as $registration): ?>
        <tr>
          <td>#<?= (int)$registration['registration_id'] ?></td>
          <td><?= e((string)$registration['full_name']) ?></td>
          <td><?= e((string)$registration['email']) ?></td>
          <td><?= e((string)$registration['event_title']) ?></td>
          <td><?= e(date('d M Y, h:i A', strtotime((string)$registration['event_date']))) ?></td>
          <td><?= e(date('d M Y, h:i A', strtotime((string)$registration['registered_at']))) ?></td>
          <td><?= e(ucfirst((string)$registration['status'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </section>
  <p class="muted" style="text-align:center;margin:24px 0">StudentHub · Admin tools are visible only to admin accounts.</p>
</main>
</body></html>
