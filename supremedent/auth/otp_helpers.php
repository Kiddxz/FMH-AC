<?php
require_once __DIR__ . '/../config/mail.php';

function otp_issue(mysqli $conn, string $email, string $purpose, string $displayName = 'SupremeDent User'): array
{
    $email = strtolower(trim($email));
    $allowed = ['signup', 'reset', 'register', 'login', 'setup'];
    if (!valid_email_address($email) || !in_array($purpose, $allowed, true)) {
        return [false, 'Please enter a valid email address.'];
    }

    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $cutoff = date('Y-m-d H:i:s', time() - 60);
    $stmt = $conn->prepare("SELECT created_at FROM auth_otps WHERE email=? AND purpose=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $email, $purpose);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($last && $last['created_at'] > $cutoff) {
        return [false, 'Please wait about 60 seconds before requesting another code.', ['retry_after' => 60, 'code' => 'cooldown']];
    }

    $hourAgo = date('Y-m-d H:i:s', time() - 3600);
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM auth_otps WHERE (email=? OR request_ip=?) AND created_at>=?");
    $stmt->bind_param('sss', $email, $ip, $hourAgo);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    if ($count >= 8) {
        return [false, 'Too many OTP requests. Please try again later.'];
    }

    if ($purpose === 'reset') {
        $stmt = $conn->prepare("SELECT first_name,last_name FROM users WHERE email=? AND status='active' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$user) {
            // Keep the response generic so account existence is not disclosed.
            return [true, 'If the account is eligible, a 6-digit code has been sent.'];
        }
        $displayName = trim($user['first_name'] . ' ' . $user['last_name']);
    }

    $resetUserId = 0;
    if ($purpose === 'reset') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $resetUserId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
        $stmt->close();
    }

    if ($purpose === 'login') {
        // Sign-in codes are only issued by auth/login.php after the password was verified.
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $active = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$active) return [false, 'This account cannot sign in.'];
    }

    if ($purpose === 'signup' || $purpose === 'register' || $purpose === 'setup') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($exists) {
            return [false, $purpose === 'register' ? 'This email is already registered to another account.' : 'This email is already registered. Please sign in or use Forgot Password.'];
        }
    }

    $otp = (string)random_int(100000, 999999);
    $hash = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + 600);
    $stmt = $conn->prepare("INSERT INTO auth_otps(email,purpose,otp_hash,expires_at,request_ip,used_at) VALUES(?,?,?,?,?,NOW())");
    $stmt->bind_param('sssss', $email, $purpose, $hash, $expires, $ip);
    $ok = $stmt->execute();
    $otpId = $stmt->insert_id;
    $stmt->close();
    if (!$ok) return [false, 'Unable to create a verification code. Please try again.'];

    // Password recovery emails also carry a one-time reset link (same 10-minute lifetime as the code).
    $extra = '';
    $tokenId = 0;
    if ($purpose === 'reset' && $resetUserId > 0) {
        $baseUrl = supremeDentAppUrl();
        if ($baseUrl !== '') {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $stmt = $conn->prepare("INSERT INTO password_resets(user_id,token_hash,expires_at,used_at) VALUES(?,?,DATE_ADD(NOW(), INTERVAL 10 MINUTE),NOW())");
            $stmt->bind_param('is', $resetUserId, $tokenHash);
            if ($stmt->execute()) {
                $tokenId = (int)$stmt->insert_id;
                $extra = "Or open this link to create a new password right away:\n" . $baseUrl . '/auth/reset_password.php?token=' . $token;
            }
            $stmt->close();
        }
    }

    // A pending/failed code stays unusable. Preserve request rows for rate limiting.
    $delivered = sendOtpEmail($email, $displayName ?: 'SupremeDent User', $otp, $purpose, $extra);
    if (!$delivered) {
        $error = supremeDentMailLastError();
        return [false, $error['message'] ?: 'Unable to send the verification email. Please try again later.',
            ['code' => $error['code'] ?: 'mail_failed', 'retry_after' => 60]];
    }
    $stmt = $conn->prepare("UPDATE auth_otps SET used_at=NULL, expires_at=DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id=?");
    $stmt->bind_param('i', $otpId);
    $stmt->execute();
    $activated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$activated) return [false, 'Unable to activate the code. Please request another code.'];
    $stmt = $conn->prepare("UPDATE auth_otps SET used_at=NOW() WHERE email=? AND purpose=? AND id<? AND used_at IS NULL");
    $stmt->bind_param('ssi', $email, $purpose, $otpId);
    $stmt->execute();
    $stmt->close();
    if ($tokenId > 0) {
        // Only the newest emailed link stays valid.
        $stmt = $conn->prepare("UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND id<? AND used_at IS NULL");
        $stmt->bind_param('ii', $resetUserId, $tokenId);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE password_resets SET used_at=NULL, expires_at=DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id=?");
        $stmt->bind_param('i', $tokenId);
        $stmt->execute();
        $stmt->close();
    }
    if ($purpose === 'reset') {
        return [true, 'A password reset email was sent. Enter the 6-digit code' . ($tokenId > 0 ? ' or open the link in the email' : '') . '. Check your inbox and spam folder. It expires in 10 minutes.',
            ['code' => 'sent', 'retry_after' => 60]];
    }
    return [true, 'Gmail accepted your 6-digit code. Check your email inbox and spam folder. The code expires in 10 minutes.',
        ['code' => 'sent', 'retry_after' => 60]];
}

function otp_consume(mysqli $conn, string $email, string $purpose, string $otp): bool
{
    $email = strtolower(trim($email));
    if (!preg_match('/^\d{6}$/', $otp)) return false;

    $stmt = $conn->prepare("SELECT id,otp_hash,expires_at,attempts,used_at FROM auth_otps WHERE email=? AND purpose=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $email, $purpose);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || $row['used_at'] !== null || (int)$row['attempts'] >= 5 || strtotime($row['expires_at']) <= time()) return false;

    if (!password_verify($otp, $row['otp_hash'])) {
        $stmt = $conn->prepare("UPDATE auth_otps SET attempts=attempts+1 WHERE id=? AND used_at IS NULL AND attempts<5");
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();
        return false;
    }

    $stmt = $conn->prepare("UPDATE auth_otps SET used_at=NOW() WHERE id=? AND used_at IS NULL AND attempts<5 AND expires_at>NOW()");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $consumed = $stmt->affected_rows === 1;
    $stmt->close();
    return $consumed;
}

/** Validates an emailed password-reset link token and marks it used. Returns the user id, or 0. */
function password_reset_token_consume(mysqli $conn, string $token): int
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return 0;
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT r.id,r.user_id FROM password_resets r JOIN users u ON u.id=r.user_id AND u.status='active' WHERE r.token_hash=? AND r.used_at IS NULL AND r.expires_at>NOW() ORDER BY r.id DESC LIMIT 1");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return 0;
    $stmt = $conn->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=? AND used_at IS NULL");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok ? (int)$row['user_id'] : 0;
}

/** Retires every outstanding reset code and link for a user once the password was changed. */
function password_reset_retire_all(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("UPDATE auth_otps o JOIN users u ON u.email=o.email SET o.used_at=NOW() WHERE u.id=? AND o.purpose='reset' AND o.used_at IS NULL");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}
