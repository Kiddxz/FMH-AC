<?php
// Always return JSON, including connection/schema failures.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    require_once __DIR__ . '/../config/security.php';
    start_secure_session();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.']);
        exit;
    }
    // Release the session while SMTP is running so other pages remain responsive.
    session_write_close();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/otp_helpers.php';
    $email = trim((string)($_POST['email'] ?? ''));
    $purpose = trim((string)($_POST['purpose'] ?? ''));
    // Public endpoint: only self sign-up and password recovery. Staff registration uses assistant/send_register_otp.php.
    if (!in_array($purpose, ['signup', 'reset'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Invalid verification request.']);
        exit;
    }
    $name = trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? ''));
    $result = otp_issue($conn, $email, $purpose, $name ?: 'SupremeDent User');
    $meta = $result[2] ?? [];
    echo json_encode(['ok' => $result[0], 'message' => $result[1],
        'code' => $meta['code'] ?? '', 'retry_after' => $meta['retry_after'] ?? 0], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('[SupremeDent OTP] Request failed: ' . get_class($error) . ': ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'The verification service is unavailable. Please try again later or contact the clinic.']);
}
