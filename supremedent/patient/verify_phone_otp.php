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
    $otp = trim((string)($_POST['otp'] ?? ''));
    if (!$normalized) {
        echo json_encode(['ok' => false, 'message' => 'Enter a valid Philippine mobile number.']);
        exit;
    }
    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['ok' => false, 'message' => 'Enter the 6-digit OTP sent to your phone.']);
        exit;
    }
    $phone = $normalized['local'];

    $stmt = $conn->prepare('SELECT id,otp_hash,expires_at,attempts,used_at,GREATEST(COALESCE(UNIX_TIMESTAMP(locked_until),0) - UNIX_TIMESTAMP(NOW()),0) lock_s FROM patient_sms_otps WHERE user_id=? AND phone=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('is', $userId, $phone);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Wrong code rules: 1st wrong = wait 15 seconds before trying again;
    // 2nd wrong = the code is retired and a new one can be requested after 30 seconds.
    if ($row && $row['used_at'] === null && (int)$row['lock_s'] > 0) {
        echo json_encode(['ok' => false, 'code' => 'locked', 'retry_after' => (int)$row['lock_s'], 'message' => 'Wrong OTP. Try again in ' . (int)$row['lock_s'] . ' seconds.']);
        exit;
    }
    if (!$row || $row['used_at'] !== null || (int)$row['attempts'] >= 2 || strtotime((string)$row['expires_at']) <= time()) {
        echo json_encode(['ok' => false, 'code' => 'expired', 'resend_after' => $row ? max(0, (int)$row['lock_s']) : 0, 'message' => 'This code is no longer valid. Please request a new code.']);
        exit;
    }

    if (!password_verify($otp, (string)$row['otp_hash'])) {
        $attempts = (int)$row['attempts'] + 1;
        if ($attempts >= 2) {
            $stmt = $conn->prepare('UPDATE patient_sms_otps SET attempts=?, used_at=NOW(), locked_until=DATE_ADD(NOW(), INTERVAL 30 SECOND) WHERE id=? AND used_at IS NULL');
            $stmt->bind_param('ii', $attempts, $row['id']);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['ok' => false, 'code' => 'retired', 'attempts' => $attempts, 'resend_after' => 30, 'message' => 'Wrong OTP again. You can request a new code in 30 seconds.']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE patient_sms_otps SET attempts=?, locked_until=DATE_ADD(NOW(), INTERVAL 15 SECOND) WHERE id=? AND used_at IS NULL');
        $stmt->bind_param('ii', $attempts, $row['id']);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => false, 'code' => 'wrong', 'attempts' => $attempts, 'retry_after' => 15, 'message' => 'Wrong OTP. Try again in 15 seconds.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE patient_sms_otps SET used_at=NOW() WHERE id=? AND used_at IS NULL AND attempts<2 AND expires_at>NOW()');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $consumed = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$consumed) throw new RuntimeException('OTP could not be consumed.');

        $stmt = $conn->prepare('INSERT INTO patient_profiles(user_id,phone,phone_verified_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE phone=VALUES(phone),phone_verified_at=NOW()');
        $stmt->bind_param('is', $userId, $phone);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        log_activity($conn, $userId, 'Verified patient contact number', 'Profile', 'SMS OTP verification');
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Contact number verified successfully.',
        'phone' => $phone,
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('[SupremeDent SMS OTP] Verify failed: ' . get_class($e) . ': ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Unable to verify the code right now. Please try again.']);
}
