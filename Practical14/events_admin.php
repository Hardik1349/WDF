<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/auth_helpers.php';
requireLogin($conn);
if (($_SESSION['role'] ?? 'student') !== 'admin') {
    header('Location: dashboard.php?access=denied');
    exit;
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = (string)$_SESSION['csrf_token'];
$uploadDir = __DIR__ . '/uploads/event_posters/';
$uploadUrl = 'uploads/event_posters/';
$maxPosterBytes = 2 * 1024 * 1024;
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function eventRedirect(string $status): never {
    header('Location: events_admin.php?status=' . rawurlencode($status));
    exit;
}
function removePoster(?string $relativePath): void {
    if (!$relativePath) return;
    $base = 'uploads/event_posters/';
    if (str_starts_with($relativePath, $base) && basename($relativePath) === substr($relativePath, strlen($base))) {
        $fullPath = __DIR__ . '/' . $relativePath;
        if (is_file($fullPath)) @unlink($fullPath);
    }
}
function storePoster(array $file, string $uploadDir, int $maxBytes, array $allowedMimeTypes): string {
    if (!isset($file['error']) || is_array($file['error'])) throw new RuntimeException('Please choose a valid poster file.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('The poster upload failed. Please try again.');
    if (!isset($file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > $maxBytes) throw new RuntimeException('Poster size must be greater than 0 and no more than 2 MB.');
    if (!is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('Invalid uploaded file.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowedMimeTypes[$mime])) throw new RuntimeException('Invalid poster type. Upload only JPG, PNG, or WEBP images.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('The uploaded file is not a valid image.');
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) throw new RuntimeException('Poster upload folder could not be created.');
    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) throw new RuntimeException('Could not save the poster. Check folder permissions.');
    return 'uploads/event_posters/' . $filename;
}

$form = ['event_id'=>'', 'title'=>'', 'description'=>'', 'event_date'=>'', 'venue'=>'', 'category'=>'', 'poster_path'=>''];
$editing = false;
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $message = 'Request could not be verified. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            $id = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
            if ($action === 'delete') {
                if (!$id || $id < 1) throw new RuntimeException('Invalid event ID.');
                $stmt = $conn->prepare('SELECT poster_path FROM events WHERE event_id = ? LIMIT 1');
                $stmt->bind_param('i', $id); $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$existing) throw new RuntimeException('Event not found.');
                $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
                $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
                removePoster($existing['poster_path'] ?? null);
                eventRedirect('deleted');
            }
            if (!in_array($action, ['add', 'update'], true)) throw new RuntimeException('Unknown action.');

            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $eventDate = trim((string)($_POST['event_date'] ?? ''));
            $venue = trim((string)($_POST['venue'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            if ($title === '' || mb_strlen($title) > 150) throw new RuntimeException('Enter an event title (maximum 150 characters).');
            if (mb_strlen($description) > 5000) throw new RuntimeException('Description must be 5,000 characters or fewer.');
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $eventDate);
            if (!$date || $date->format('Y-m-d\TH:i') !== $eventDate) throw new RuntimeException('Choose a valid event date and time.');
            if ($venue === '' || mb_strlen($venue) > 150) throw new RuntimeException('Enter a venue (maximum 150 characters).');
            if ($category === '' || mb_strlen($category) > 60) throw new RuntimeException('Enter a category (maximum 60 characters).');

            $oldPoster = '';
            if ($action === 'update') {
                if (!$id || $id < 1) throw new RuntimeException('Invalid event ID.');
                $stmt = $conn->prepare('SELECT poster_path FROM events WHERE event_id = ? LIMIT 1');
                $stmt->bind_param('i', $id); $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$existing) throw new RuntimeException('Event not found.');
                $oldPoster = (string)($existing['poster_path'] ?? '');
            }
            $newPoster = storePoster($_FILES['poster'] ?? ['error'=>UPLOAD_ERR_NO_FILE], $uploadDir, $maxPosterBytes, $allowedMimeTypes);
            $posterPath = $newPoster !== '' ? $newPoster : $oldPoster;

            if ($action === 'add') {
                $stmt = $conn->prepare('INSERT INTO events (title, description, event_date, venue, category, poster_path) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssss', $title, $description, $date->format('Y-m-d H:i:s'), $venue, $category, $posterPath);
                $stmt->execute(); $stmt->close();
                eventRedirect('added');
            }
            $eventDateDb = $date->format('Y-m-d H:i:s');
            $stmt = $conn->prepare('UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ?, poster_path = ? WHERE event_id = ?');
            $stmt->bind_param('ssssssi', $title, $description, $eventDateDb, $venue, $category, $posterPath, $id);
            $stmt->execute(); $stmt->close();
            if ($newPoster !== '' && $oldPoster !== '') removePoster($oldPoster);
            eventRedirect('updated');
        } catch (RuntimeException $ex) {
            $message = $ex->getMessage(); $messageType = 'error';
            $form = [
                'event_id'=>(string)($_POST['event_id'] ?? ''), 'title'=>trim((string)($_POST['title'] ?? '')),
                'description'=>trim((string)($_POST['description'] ?? '')), 'event_date'=>(string)($_POST['event_date'] ?? ''),
                'venue'=>trim((string)($_POST['venue'] ?? '')), 'category'=>trim((string)($_POST['category'] ?? '')),
                'poster_path'=>(string)($_POST['current_poster'] ?? '')
            ];
            $editing = $action === 'update';
        } catch (mysqli_sql_exception $ex) {
            error_log('Practical 12 event management error: ' . $ex->getMessage());
            $message = 'Database operation failed. Run the Practical 12 SQL setup and try again.'; $messageType = 'error';
        }
    }
}

$statusMessages = [
    'added'=>['Event added successfully.','success'], 'updated'=>['Event updated successfully.','success'],
    'deleted'=>['Event deleted successfully.','success']
];
$status = (string)($_GET['status'] ?? '');
if ($message === '' && isset($statusMessages[$status])) [$message, $messageType] = $statusMessages[$status];

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($message === '' && $editId && $editId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare('SELECT event_id, title, description, event_date, venue, category, poster_path FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('i', $editId); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) {
        $row['event_date'] = date('Y-m-d\TH:i', strtotime($row['event_date']));
        $form = $row; $editing = true;
    } else { $message = 'Event not found.'; $messageType = 'error'; }
}

$events = [];
try {
    $result = $conn->query('SELECT event_id, title, description, event_date, venue, category, poster_path FROM events ORDER BY event_date DESC, event_id DESC');
    while ($row = $result->fetch_assoc()) $events[] = $row;
    $result->free();
} catch (mysqli_sql_exception $ex) {
    $message = 'Could not load events. Please run database/practical12_event_management.sql once.';
    $messageType = 'error';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Events | StudentHub</title><link rel="stylesheet" href="CSS/style.css">
<style>
.event-admin{max-width:1180px;margin:30px auto;padding:0 16px}.event-admin h1,.event-admin h2{color:#4d36a8}.event-panel{background:#fff;padding:22px;border-radius:14px;box-shadow:0 5px 20px #0000000d;margin-bottom:22px}.event-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.event-form .full{grid-column:1/-1}.event-form label{display:block;font-weight:600;margin-bottom:5px}.event-form input,.event-form textarea,.event-form select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px}.event-form textarea{min-height:95px}.event-btn{display:inline-block;padding:9px 14px;border:0;border-radius:7px;background:#5b3fd3;color:#fff;text-decoration:none;cursor:pointer}.event-btn.secondary{background:#475569}.event-btn.danger{background:#b42318}.event-msg{padding:12px 14px;border-radius:8px;margin:14px 0}.event-msg.success{background:#dcfce7;color:#166534}.event-msg.error{background:#fee2e2;color:#991b1b}.event-table-wrap{overflow-x:auto}.event-table{width:100%;border-collapse:collapse;min-width:760px}.event-table th,.event-table td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:top}.poster-thumb{width:100px;height:70px;object-fit:cover;border-radius:6px}.actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.actions form{margin:0}.hint{font-size:.9rem;color:#64748b}.top-links{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}@media(max-width:650px){.event-form{grid-template-columns:1fr}.event-form .full{grid-column:auto}.event-panel{padding:15px}}
</style></head><body>
<main class="event-admin">
<div class="top-links"><a class="event-btn secondary" href="admin_dashboard.php">← Admin Dashboard</a><a class="event-btn secondary" href="students.php">Manage Students</a><a class="event-btn secondary" href="logout.php">Log Out</a></div>
<h1>Event Management</h1><p>Add and maintain campus events and their poster images. This page is restricted to admin users.</p>
<?php if ($message !== ''): ?><div class="event-msg <?= e($messageType) ?>" role="status"><?= e($message) ?></div><?php endif; ?>
<section class="event-panel"><h2><?= $editing ? 'Update Event' : 'Add New Event' ?></h2>
<form class="event-form" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>"><input type="hidden" name="event_id" value="<?= e((string)$form['event_id']) ?>"><input type="hidden" name="current_poster" value="<?= e((string)$form['poster_path']) ?>">
<div><label for="title">Event title *</label><input id="title" name="title" maxlength="150" required value="<?= e((string)$form['title']) ?>"></div>
<div><label for="category">Category *</label><input id="category" name="category" maxlength="60" required placeholder="Workshop, Sports, Cultural..." value="<?= e((string)$form['category']) ?>"></div>
<div><label for="event_date">Date and time *</label><input id="event_date" type="datetime-local" name="event_date" required value="<?= e((string)$form['event_date']) ?>"></div>
<div><label for="venue">Venue *</label><input id="venue" name="venue" maxlength="150" required value="<?= e((string)$form['venue']) ?>"></div>
<div class="full"><label for="description">Description</label><textarea id="description" name="description" maxlength="5000"><?= e((string)$form['description']) ?></textarea></div>
<div class="full"><label for="poster">Event poster (optional)</label><input id="poster" name="poster" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><p class="hint">Accepted formats: JPG/JPEG, PNG, WEBP. Maximum size: 2 MB. File type is checked on the server.</p>
<?php if (!empty($form['poster_path'])): ?><p>Current poster:</p><img class="poster-thumb" src="<?= e((string)$form['poster_path']) ?>" alt="Current event poster"><?php endif; ?></div>
<div class="full"><button class="event-btn" type="submit"><?= $editing ? 'Save Changes' : 'Add Event' ?></button> <?php if ($editing): ?><a class="event-btn secondary" href="events_admin.php">Cancel edit</a><?php endif; ?></div>
</form></section>
<section class="event-panel"><h2>All Events (<?= count($events) ?>)</h2><div class="event-table-wrap"><table class="event-table"><thead><tr><th>Poster</th><th>Event</th><th>Date &amp; Time</th><th>Venue</th><th>Category</th><th>Actions</th></tr></thead><tbody>
<?php if (!$events): ?><tr><td colspan="6">No events found. Add the first event using the form above.</td></tr><?php endif; ?>
<?php foreach ($events as $event): ?><tr><td><?php if (!empty($event['poster_path'])): ?><img class="poster-thumb" src="<?= e((string)$event['poster_path']) ?>" alt="Poster for <?= e((string)$event['title']) ?>"><?php else: ?><span class="hint">No poster</span><?php endif; ?></td><td><strong><?= e((string)$event['title']) ?></strong><br><span class="hint"><?= e((string)$event['description']) ?></span></td><td><?= e(date('d M Y, h:i A', strtotime((string)$event['event_date']))) ?></td><td><?= e((string)$event['venue']) ?></td><td><?= e((string)$event['category']) ?></td><td><div class="actions"><a class="event-btn" href="events_admin.php?edit=<?= (int)$event['event_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this event and its poster?');"><input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>"><button class="event-btn danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<p class="hint">Security: admin-only access, CSRF token, prepared statements, server-side validation, random poster filenames, MIME/image validation, and a 2 MB upload limit.</p>
</main></body></html>
