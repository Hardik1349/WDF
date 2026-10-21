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
$csrf = $_SESSION['csrf_token'];
$message = '';
$messageType = 'success';
$editing = false;
$form = ['student_id'=>'', 'full_name'=>'', 'email'=>'', 'mobile'=>'', 'course'=>'', 'study_year'=>'1', 'gender'=>''];

function redirectWithMessage(string $kind): never {
    header('Location: students.php?status=' . rawurlencode($kind));
    exit;
}

// Add, update and delete all use POST requests and prepared MySQLi statements.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrf, $postedToken)) {
        $message = 'Request could not be verified. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'delete') {
                $id = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 1) throw new RuntimeException('Invalid student ID.');
                $stmt = $conn->prepare('DELETE FROM students WHERE student_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                redirectWithMessage($affected ? 'deleted' : 'notfound');
            }

            $id = (int)($_POST['student_id'] ?? 0);
            $name = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $mobile = trim((string)($_POST['mobile'] ?? ''));
            $course = trim((string)($_POST['course'] ?? ''));
            $year = filter_var($_POST['study_year'] ?? null, FILTER_VALIDATE_INT);
            $gender = trim((string)($_POST['gender'] ?? ''));
            $plainPassword = (string)($_POST['student_password'] ?? '');
            $form = ['student_id'=>(string)$id, 'full_name'=>$name, 'email'=>$email, 'mobile'=>$mobile, 'course'=>$course, 'study_year'=>(string)($year ?: 1), 'gender'=>$gender];
            $editing = ($action === 'update');

            if (!in_array($action, ['add', 'update'], true)) throw new RuntimeException('Unknown action.');
            if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Enter a student name (maximum 100 characters).');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) throw new RuntimeException('Enter a valid email address (maximum 150 characters).');
            if ($mobile === '' || !preg_match('/^[0-9+()\- ]{7,15}$/', $mobile)) throw new RuntimeException('Enter a valid mobile number (7–15 characters).');
            if ($course === '' || mb_strlen($course) > 100) throw new RuntimeException('Enter a course (maximum 100 characters).');
            if (!$year || $year < 1 || $year > 8) throw new RuntimeException('Study year must be between 1 and 8.');
            if (!in_array($gender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) throw new RuntimeException('Choose a valid gender option.');

            // Check duplicate email, excluding the current row during an edit.
            if ($action === 'update') {
                if ($id < 1) throw new RuntimeException('Invalid student ID.');
                $check = $conn->prepare('SELECT student_id FROM students WHERE email = ? AND student_id <> ? LIMIT 1');
                $check->bind_param('si', $email, $id);
            } else {
                if (strlen($plainPassword) < 8) throw new RuntimeException('Initial password must contain at least 8 characters.');
                $check = $conn->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
                $check->bind_param('s', $email);
            }
            $check->execute();
            $duplicate = $check->get_result()->fetch_assoc();
            $check->close();
            if ($duplicate) throw new RuntimeException('That email address is already used by another student.');

            if ($action === 'add') {
                $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO students (full_name, email, mobile, password_hash, course, study_year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssis', $name, $email, $mobile, $passwordHash, $course, $year, $gender);
                $stmt->execute();
                $stmt->close();
                redirectWithMessage('added');
            } else {
                $stmt = $conn->prepare('UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, study_year = ?, gender = ? WHERE student_id = ?');
                $stmt->bind_param('ssssisi', $name, $email, $mobile, $course, $year, $gender, $id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                redirectWithMessage($affected >= 0 ? 'updated' : 'failed');
            }
        } catch (RuntimeException $ex) {
            $message = $ex->getMessage();
            $messageType = 'error';
        } catch (mysqli_sql_exception $ex) {
            error_log('Practical 11 student management error: ' . $ex->getMessage());
            $message = ((int)$ex->getCode() === 1062) ? 'That email address is already registered.' : 'Database operation failed. Please check the database setup and try again.';
            $messageType = 'error';
        }
    }
}

$statusMessages = [
    'added' => ['Student added successfully.', 'success'],
    'updated' => ['Student record updated successfully.', 'success'],
    'deleted' => ['Student deleted successfully.', 'success'],
    'notfound' => ['No student was found to delete.', 'error'],
    'failed' => ['The student record could not be updated.', 'error'],
];
$status = (string)($_GET['status'] ?? '');
if ($message === '' && isset($statusMessages[$status])) {
    [$message, $messageType] = $statusMessages[$status];
}

// Load a record into the form for editing.
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($message === '' && $editId && $editId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conn->prepare('SELECT student_id, full_name, email, mobile, course, study_year, gender FROM students WHERE student_id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) { $form = $row; $editing = true; }
    else { $message = 'Student record not found.'; $messageType = 'error'; }
}

$search = trim((string)($_GET['search'] ?? ''));
$filterCourse = trim((string)($_GET['course'] ?? ''));
$filterYear = trim((string)($_GET['year'] ?? ''));
$sql = 'SELECT student_id, full_name, email, mobile, course, study_year, gender, created_at FROM students WHERE 1=1';
$types = '';
$params = [];
if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR email LIKE ? OR mobile LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sss'; array_push($params, $like, $like, $like);
}
if ($filterCourse !== '') { $sql .= ' AND course = ?'; $types .= 's'; $params[] = $filterCourse; }
if ($filterYear !== '' && ctype_digit($filterYear) && (int)$filterYear >= 1 && (int)$filterYear <= 8) { $yearFilter = (int)$filterYear; $sql .= ' AND study_year = ?'; $types .= 'i'; $params[] = $yearFilter; }
$sql .= ' ORDER BY student_id DESC';
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$coursesResult = $conn->query('SELECT DISTINCT course FROM students ORDER BY course');
$courses = $coursesResult->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Management | StudentHub</title><link rel="stylesheet" href="CSS/style.css">
<style>
:root{--purple:#5b3fd3;--ink:#24233a;--muted:#6e6c7f;--line:#e7e5ef;--bg:#f5f4fa}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Arial,sans-serif}.wrap{max-width:1180px;margin:28px auto;padding:0 16px}.top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.top h1{margin:0;color:#4d36a8}.muted{color:var(--muted);font-size:14px}.panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;margin:20px 0;box-shadow:0 5px 18px #21124d08}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field label{display:block;font-size:14px;font-weight:bold;margin-bottom:6px}.field input,.field select{width:100%;padding:11px;border:1px solid #d6d3e4;border-radius:7px;font:inherit;background:white}.actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}.btn{display:inline-block;border:0;border-radius:7px;padding:10px 14px;background:var(--purple);color:#fff;text-decoration:none;cursor:pointer;font:inherit}.btn.secondary{background:#eeebfb;color:#45319d}.btn.danger{background:#b42338}.alert{padding:12px 14px;border-radius:8px;margin:14px 0}.alert.success{background:#e8f8ee;color:#176536}.alert.error{background:#fff0f0;color:#9e1c28}.filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:800px}th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--line);font-size:14px}th{background:#f8f7fc}td form{display:inline} .inline-actions{display:flex;gap:7px;align-items:center}.count{color:var(--muted);font-size:14px}@media(max-width:700px){.grid,.filters{grid-template-columns:1fr}.panel{padding:15px}.top h1{font-size:24px}}
</style></head><body><main class="wrap">
<div class="top"><div><h1>Student Management</h1><p class="muted">Add, view, edit, delete, search and filter student records.</p></div><div class="actions"><a class="btn secondary" href="admin_dashboard.php">Admin Dashboard</a><a class="btn secondary" href="dashboard.php">Student Dashboard</a><a class="btn danger" href="logout.php">Log Out</a></div></div>
<?php if ($message !== ''): ?><div class="alert <?= e($messageType) ?>" role="status"><?= e($message) ?></div><?php endif; ?>
<section class="panel" id="student-form"><h2><?= $editing ? 'Edit Student' : 'Add New Student' ?></h2><form method="post" action="students.php#student-form"><input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="student_id" value="<?= e((string)$form['student_id']) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>"><div class="grid">
<div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" required value="<?= e((string)$form['full_name']) ?>"></div>
<div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="150" required value="<?= e((string)$form['email']) ?>"></div>
<div class="field"><label for="mobile">Mobile number</label><input id="mobile" name="mobile" maxlength="15" pattern="[0-9+()\- ]{7,15}" required value="<?= e((string)$form['mobile']) ?>"></div>
<div class="field"><label for="course">Course</label><input id="course" name="course" maxlength="100" required placeholder="e.g. B.Tech IT" value="<?= e((string)$form['course']) ?>"></div>
<div class="field"><label for="study_year">Study year</label><select id="study_year" name="study_year" required><?php for ($i=1;$i<=8;$i++): ?><option value="<?= $i ?>" <?= (string)$form['study_year']===(string)$i?'selected':'' ?>>Year <?= $i ?></option><?php endfor; ?></select></div>
<div class="field"><label for="gender">Gender</label><select id="gender" name="gender" required><?php foreach (['Male','Female','Other','Prefer not to say'] as $g): ?><option value="<?= e($g) ?>" <?= (string)$form['gender']===$g?'selected':'' ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
<?php if (!$editing): ?><div class="field"><label for="student_password">Initial password</label><input id="student_password" name="student_password" type="password" minlength="8" autocomplete="new-password" required><small class="muted">At least 8 characters. Stored as a password hash.</small></div><?php else: ?><div class="field"><label>Password</label><input value="Password is unchanged during editing" disabled></div><?php endif; ?>
</div><div class="actions" style="margin-top:16px"><button class="btn" type="submit"><?= $editing ? 'Save Changes' : 'Add Student' ?></button><?php if ($editing): ?><a class="btn secondary" href="students.php#student-form">Cancel Edit</a><?php endif; ?></div></form></section>
<section class="panel"><h2>Search and Filter</h2><form method="get" action="students.php" class="filters"><div class="field"><label for="search">Search name, email or mobile</label><input id="search" name="search" value="<?= e($search) ?>" placeholder="Type to search..."></div><div class="field"><label for="course-filter">Course</label><select id="course-filter" name="course"><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= e($c['course']) ?>" <?= $filterCourse===$c['course']?'selected':'' ?>><?= e($c['course']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="year-filter">Study year</label><select id="year-filter" name="year"><option value="">All years</option><?php for($i=1;$i<=8;$i++): ?><option value="<?= $i ?>" <?= $filterYear===(string)$i?'selected':'' ?>>Year <?= $i ?></option><?php endfor; ?></select></div><div class="actions"><button class="btn" type="submit">Apply</button><a class="btn secondary" href="students.php">Reset</a></div></form></section>
<section class="panel"><div class="top"><h2>Student Records</h2><span class="count">Showing <?= count($students) ?> record(s)</span></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Gender</th><th>Actions</th></tr></thead><tbody>
<?php if (!$students): ?><tr><td colspan="8">No student records match your search/filter.</td></tr><?php else: foreach($students as $student): ?><tr><td><?= (int)$student['student_id'] ?></td><td><?= e($student['full_name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['mobile']) ?></td><td><?= e($student['course']) ?></td><td><?= (int)$student['study_year'] ?></td><td><?= e($student['gender']) ?></td><td><div class="inline-actions"><a class="btn secondary" href="students.php?edit=<?= (int)$student['student_id'] ?>#student-form">Edit</a><form method="post" action="students.php" onsubmit="return confirm('Delete this student record? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>"><button class="btn danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section><p class="muted">All student record changes use prepared MySQLi statements. Only logged-in administrators can access this module.</p></main></body></html>
