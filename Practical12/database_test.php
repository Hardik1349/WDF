<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

$students = (int) $conn->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'];
$events = (int) $conn->query('SELECT COUNT(*) AS total FROM events')->fetch_assoc()['total'];
$registrations = (int) $conn->query('SELECT COUNT(*) AS total FROM registrations')->fetch_assoc()['total'];
$eventResult = $conn->query('SELECT title, event_date, venue, category FROM events ORDER BY event_date ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Database Test</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        .db-test { width: 90%; max-width: 1000px; margin: 45px auto; }
        .db-status { padding: 18px; border-radius: 10px; background: #e8f8ee; color: #176534; margin: 20px 0; }
        .db-counts { display: grid; grid-template-columns: repeat(auto-fit,minmax(160px,1fr)); gap: 16px; margin: 20px 0; }
        .db-count { padding: 20px; border-radius: 12px; background: #f5f2ff; }
        .db-count strong { display: block; font-size: 30px; color: #5b3fd3; }
        .db-table-wrap { overflow-x: auto; }
        .db-table { width: 100%; border-collapse: collapse; background: white; }
        .db-table th, .db-table td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        .db-table th { background: #f5f2ff; }
    </style>
</head>
<body>
    <main class="db-test">
        <section class="page-heading">
            <small>PRACTICAL 8</small>
            <h1>StudentHub Database Test</h1>
            <p>MySQL database connection and table check</p>
        </section>
        <div class="db-status"><strong>Connection successful!</strong> PHP is connected to the <code>studenthub</code> MySQL database.</div>
        <h2>Table records</h2>
        <section class="db-counts">
            <div class="db-count">Students<strong><?= $students ?></strong></div>
            <div class="db-count">Events<strong><?= $events ?></strong></div>
            <div class="db-count">Registrations<strong><?= $registrations ?></strong></div>
        </section>
        <h2>Events stored in MySQL</h2>
        <div class="db-table-wrap">
            <table class="db-table">
                <thead><tr><th>Event</th><th>Date and time</th><th>Venue</th><th>Category</th></tr></thead>
                <tbody>
                <?php if ($eventResult->num_rows === 0): ?>
                    <tr><td colspan="4">No events found. You can add events using phpMyAdmin.</td></tr>
                <?php else: ?>
                    <?php while ($event = $eventResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($event['venue'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p><a class="primary-btn" href="index.html">Return to CampusConnect</a></p>
    </main>
</body>
</html>
