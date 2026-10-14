<?php
/** Sends the email verification code for a patient being registered by an assistant. JSON only. */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    require_once __DIR__ . '/../config/security.php';
    start_secure_session();
    require_once __DIR__ . '/../config/database.php';
    require_portal_role($conn, 'assistant', true);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    require_once __DIR__ . '/../auth/otp_helpers.php';
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $name = trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? ''));
    $result = otp_issue($conn, $email, 'register', $name ?: 'SupremeDent Patient');
    $meta = $result[2] ?? [];
    $message = $result[0] ? 'Verification code sent to ' . $email . '. Ask the patient for the 6-digit code. It expires in 10 minutes.' : $result[1];
    echo json_encode(['ok' => $result[0], 'message' => $message, 'retry_after' => $meta['retry_after'] ?? 0], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('[SupremeDent register OTP] ' . get_class($error) . ': ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'The verification service is unavailable. Please try again later.']);
}
