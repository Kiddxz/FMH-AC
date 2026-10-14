<?php
/** Live signup check: tells the form whether an email address is already registered. */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    require_once __DIR__ . '/../config/security.php';
    start_secure_session();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo json_encode(['ok' => false]);
        exit;
    }
    session_write_close();
    require_once __DIR__ . '/../config/database.php';
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!valid_email_address($email)) {
        echo json_encode(['ok' => true, 'valid' => false, 'available' => false]);
        exit;
    }
    $st = $conn->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $st->bind_param('s', $email);
    $st->execute();
    $taken = $st->get_result()->num_rows > 0;
    $st->close();
    echo json_encode(['ok' => true, 'valid' => true, 'available' => !$taken]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false]);
}
