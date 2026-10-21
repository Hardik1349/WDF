<?php
declare(strict_types=1);

// Practical 7: server-side validation and CSV storage.
function clean_text(string $value): string {
    $value = trim($value);
    // Remove HTML tags and control characters before validation/storage.
    $value = strip_tags($value);
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
}
function show_result(string $title, string $message, bool $success): never {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $class = $success ? 'success' : 'failure';
    $button = $success ? 'Return to Home' : 'Go Back';
    $href = $success ? 'index.html' : 'javascript:history.back()';
    http_response_code($success ? 200 : 400);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $safeTitle . ' | CampusConnect</title><link rel="stylesheet" href="CSS/style.css"></head><body>';
    echo '<main class="server-result"><section class="server-result-card ' . $class . '">';
    echo '<h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>';
    echo '<a class="result-link" href="' . $href . '">' . $button . '</a>';
    echo '</section></main></body></html>';
    exit;
}
function append_csv(string $filename, array $headers, array $row): bool {
    $folder = __DIR__ . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        return false;
    }
    $path = $folder . DIRECTORY_SEPARATOR . $filename;
    $isNew = !file_exists($path) || filesize($path) === 0;
    $handle = fopen($path, 'ab');
    if ($handle === false) return false;
    $ok = false;
    if (flock($handle, LOCK_EX)) {
        if ($isNew) fputcsv($handle, $headers);
        $ok = fputcsv($handle, $row) !== false;
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);
    return $ok;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_result('Invalid Request', 'Please submit a form using the form page.', false);
}
$formType = $_POST['formType'] ?? '';

if ($formType === 'registration') {
    $name = clean_text((string)($_POST['fullName'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $mobile = preg_replace('/\s+/', '', (string)($_POST['mobile'] ?? '')) ?? '';
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirmPassword'] ?? '');
    $course = clean_text((string)($_POST['course'] ?? ''));
    $year = clean_text((string)($_POST['year'] ?? ''));
    $gender = clean_text((string)($_POST['gender'] ?? ''));
    $terms = isset($_POST['terms']);

    $errors = [];
    if ($name === '' || !preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/", $name)) $errors[] = 'Enter a valid full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) $errors[] = 'Enter a valid 10-digit Indian mobile number.';
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{8,}$/', $password)) $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, number and special character.';
    if ($password !== $confirmPassword) $errors[] = 'Password and confirm password do not match.';
    if (!in_array($course, ['BTech IT', 'BTech CSE', 'BCA', 'MCA', 'Other'], true)) $errors[] = 'Select a valid course.';
    if (!in_array($year, ['1', '2', '3', '4'], true)) $errors[] = 'Select a valid year of study.';
    if (!in_array($gender, ['Female', 'Male', 'Other'], true)) $errors[] = 'Select a valid gender option.';
    if (!$terms) $errors[] = 'You must accept the terms and conditions.';
    if ($errors) show_result('Registration Error', implode(' ', $errors), false);

    // Never store a plain-text password. Only the one-way password hash is saved.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $saved = append_csv('registrations.csv',
        ['submitted_at', 'full_name', 'email', 'mobile', 'password_hash', 'course', 'year', 'gender', 'terms_accepted'],
        [date('c'), $name, $email, $mobile, $passwordHash, $course, $year, $gender, 'Yes']);
    if (!$saved) show_result('Storage Error', 'Your registration could not be saved. Check that the storage folder is writable.', false);
    show_result('Registration Successful', 'Thank you, ' . $name . '! Your registration was validated on the server and saved successfully.', true);
}

if ($formType === 'contact') {
    $name = clean_text((string)($_POST['name'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $subject = clean_text((string)($_POST['subject'] ?? ''));
    $message = clean_text((string)($_POST['message'] ?? ''));
    $errors = [];
    if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Enter your name (maximum 80 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $errors[] = 'Enter a valid email address.';
    if ($subject === '' || mb_strlen($subject) > 120) $errors[] = 'Enter a subject (maximum 120 characters).';
    if ($message === '' || mb_strlen($message) > 2000) $errors[] = 'Enter a message (maximum 2000 characters).';
    if ($errors) show_result('Contact Form Error', implode(' ', $errors), false);
    $saved = append_csv('contacts.csv', ['submitted_at', 'name', 'email', 'subject', 'message'], [date('c'), $name, $email, $subject, $message]);
    if (!$saved) show_result('Storage Error', 'Your message could not be saved. Check that the storage folder is writable.', false);
    show_result('Message Sent Successfully', 'Thank you, ' . $name . '! Your message has been validated and saved successfully.', true);
}
show_result('Invalid Form', 'We could not identify this form. Please use the registration or contact page.', false);
