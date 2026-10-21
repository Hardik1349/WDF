<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth_helpers.php';

// Keep API responses as JSON instead of redirecting to an HTML login page.
startSecureSession();
tryRememberLogin($conn);
function apiResponse(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['user_id'])) apiResponse(401, ['success'=>false, 'message'=>'Please log in first.']);
if (($_SESSION['role'] ?? 'student') !== 'admin') apiResponse(403, ['success'=>false, 'message'=>'Admin access is required.']);
$lastActivity = (int)($_SESSION['last_activity'] ?? time());
if (time() - $lastActivity > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    apiResponse(401, ['success'=>false, 'message'=>'Your session expired. Please log in again.']);
}
$_SESSION['last_activity'] = time();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    apiResponse(405, ['success'=>false, 'message'=>'Method not allowed.']);
}

if ($method !== 'GET') {
    $sentToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
        apiResponse(403, ['success'=>false, 'message'=>'Security token check failed. Refresh the page and try again.']);
    }
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) apiResponse(400, ['success'=>false, 'message'=>'Please send valid JSON data.']);
    return $data;
}
function validateEvent(array $data): array {
    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $eventDate = trim((string)($data['event_date'] ?? ''));
    $venue = trim((string)($data['venue'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    if ($title === '' || mb_strlen($title) > 150) apiResponse(422, ['success'=>false, 'message'=>'Title is required and must be 150 characters or fewer.']);
    if (mb_strlen($description) > 5000) apiResponse(422, ['success'=>false, 'message'=>'Description must be 5,000 characters or fewer.']);
    $date = DateTime::createFromFormat('Y-m-d\\TH:i', $eventDate);
    if (!$date || $date->format('Y-m-d\\TH:i') !== $eventDate) apiResponse(422, ['success'=>false, 'message'=>'Choose a valid event date and time.']);
    if ($venue === '' || mb_strlen($venue) > 150) apiResponse(422, ['success'=>false, 'message'=>'Venue is required and must be 150 characters or fewer.']);
    if ($category === '' || mb_strlen($category) > 60) apiResponse(422, ['success'=>false, 'message'=>'Category is required and must be 60 characters or fewer.']);
    return [$title, $description, $date->format('Y-m-d H:i:s'), $venue, $category];
}

try {
    if ($method === 'GET') {
        $result = $conn->query('SELECT event_id, title, description, event_date, venue, category FROM events ORDER BY event_date ASC, event_id DESC');
        $events = [];
        while ($row = $result->fetch_assoc()) $events[] = $row;
        $result->free();
        apiResponse(200, ['success'=>true, 'events'=>$events]);
    }

    if ($method === 'POST') {
        $data = readJsonBody();
        [$title, $description, $eventDate, $venue, $category] = validateEvent($data);
        $stmt = $conn->prepare('INSERT INTO events (title, description, event_date, venue, category) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $title, $description, $eventDate, $venue, $category);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        apiResponse(201, ['success'=>true, 'message'=>'Event added successfully.', 'event_id'=>$id]);
    }

    if ($method === 'PUT') {
        $data = readJsonBody();
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) apiResponse(422, ['success'=>false, 'message'=>'A valid event ID is required.']);
        [$title, $description, $eventDate, $venue, $category] = validateEvent($data);
        $stmt = $conn->prepare('UPDATE events SET title=?, description=?, event_date=?, venue=?, category=? WHERE event_id=?');
        $stmt->bind_param('sssssi', $title, $description, $eventDate, $venue, $category, $id);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        $check = $conn->prepare('SELECT event_id FROM events WHERE event_id=? LIMIT 1');
        $check->bind_param('i', $id); $check->execute(); $exists = (bool)$check->get_result()->fetch_assoc(); $check->close();
        if (!$exists) apiResponse(404, ['success'=>false, 'message'=>'Event not found.']);
        apiResponse(200, ['success'=>true, 'message'=>'Event updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = readJsonBody();
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) apiResponse(422, ['success'=>false, 'message'=>'A valid event ID is required.']);
        $stmt = $conn->prepare('DELETE FROM events WHERE event_id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $deleted = $stmt->affected_rows; $stmt->close();
        if (!$deleted) apiResponse(404, ['success'=>false, 'message'=>'Event not found.']);
        apiResponse(200, ['success'=>true, 'message'=>'Event deleted successfully.']);
    }
} catch (mysqli_sql_exception $e) {
    error_log('Practical 13 Events API: ' . $e->getMessage());
    apiResponse(500, ['success'=>false, 'message'=>'Database error. Check that the studenthub events table is set up.']);
}
