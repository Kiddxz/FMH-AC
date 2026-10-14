<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../config/security.php';
    start_secure_session();
    require_once __DIR__ . '/../config/database.php';
    require_portal_role($conn, 'patient', true);
    require_once __DIR__ . '/../config/sms.php';

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

    $userId = (int)$_SESSION['user_id'];
    $normalized = supremeDentNormalizePhone((string)($_POST['phone'] ?? ''));
    if (!$normalized) {
        echo json_encode(['ok' => false, 'message' => 'Enter a valid Philippine mobile number, for example 09171234567.']);
        exit;
    }
    $phone = $normalized['local'];
    $ip = client_ip();

    // One request per 30 seconds for the same patient/phone; after two wrong codes the resend
    // also waits 30 seconds (locked_until on the retired code).
    $stmt = $conn->prepare('SELECT GREATEST(UNIX_TIMESTAMP(created_at) + 30, COALESCE(UNIX_TIMESTAMP(locked_until), 0)) - UNIX_TIMESTAMP(NOW()) wait_s FROM patient_sms_otps WHERE user_id=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $wait = $last ? (int)$last['wait_s'] : 0;
    if ($wait > 0) {
        echo json_encode(['ok' => false, 'message' => 'Please wait ' . $wait . ' seconds before requesting another code.', 'retry_after' => $wait, 'code' => 'cooldown']);
        exit;
    }

    // Limit repeated requests from the same account or IP.
    $hourAgo = date('Y-m-d H:i:s', time() - 3600);
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM patient_sms_otps WHERE (user_id=? OR request_ip=?) AND created_at>=?');
    $stmt->bind_param('iss', $userId, $ip, $hourAgo);
    $stmt->execute();
    $requestCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    if ($requestCount >= 8) {
        echo json_encode(['ok' => false, 'message' => 'Too many OTP requests. Please try again later.']);
        exit;
    }

    $otp = (string)random_int(100000, 999999);
    $hash = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + 300);

    // Insert as inactive first. It only becomes usable after the SMS provider accepts the request.
    $stmt = $conn->prepare('INSERT INTO patient_sms_otps(user_id,phone,otp_hash,expires_at,request_ip,used_at) VALUES(?,?,?,?,?,NOW())');
    $stmt->bind_param('issss', $userId, $phone, $hash, $expires, $ip);
    if (!$stmt->execute()) {
        $stmt->close();
        echo json_encode(['ok' => false, 'message' => 'Unable to create a verification code. Please try again.']);
        exit;
    }
    $otpId = (int)$stmt->insert_id;
    $stmt->close();

    $sent = supremeDentSendSmsOtp($phone, $otp);
    if (!$sent[0]) {
        echo json_encode(['ok' => false, 'message' => $sent[1], 'retry_after' => 30]);
        exit;
    }

    $stmt = $conn->prepare('UPDATE patient_sms_otps SET used_at=NULL, expires_at=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?');
    $stmt->bind_param('i', $otpId);
    $stmt->execute();
    $activated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$activated) {
        echo json_encode(['ok' => false, 'message' => 'Unable to activate the verification code. Please request another code.']);
        exit;
    }

    // Invalidate any older unused code for this patient.
    $stmt = $conn->prepare('UPDATE patient_sms_otps SET used_at=NOW() WHERE user_id=? AND id<? AND used_at IS NULL');
    $stmt->bind_param('ii', $userId, $otpId);
    $stmt->execute();
    $stmt->close();

    $loggedOnly = ($sent[2]['provider'] ?? '') === 'otp.log';
    echo json_encode([
        'ok' => true,
        'delivery' => $loggedOnly ? 'log' : 'sms',
        'message' => $loggedOnly
            ? 'Temporary testing mode: the 6-digit OTP for ' . supremeDentMaskPhone($phone) . ' was saved to storage/otp.log. It expires in 5 minutes.'
            : 'A 6-digit OTP was sent to ' . supremeDentMaskPhone($phone) . '. It expires in 5 minutes.',
        'masked_phone' => supremeDentMaskPhone($phone),
        'retry_after' => 30,
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('[SupremeDent SMS OTP] Send failed: ' . get_class($e) . ': ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'SMS verification is temporarily unavailable. Please try again later.']);
}
