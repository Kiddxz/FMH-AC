<?php
/**
 * Sign-in helpers shared by auth/login.php and auth/login_verify.php.
 */
require_once __DIR__ . '/../config/security.php';

const SD_LOGIN_ROLES = ['patient', 'assistant', 'dentist', 'superadmin'];

/** Roles that must enter an emailed 6-digit code after the password (Super Admin > Roles & Permissions). */
function sd_login_otp_roles(mysqli $conn): array
{
    if (sd_local_config()['disable_login_otp']) return [];
    $raw = (string)(clinic_settings($conn)['login_otp_roles'] ?? 'superadmin');
    return array_values(array_intersect(SD_LOGIN_ROLES, array_map('trim', explode(',', $raw))));
}

function sd_login_otp_required(mysqli $conn, string $role): bool
{
    return in_array($role, sd_login_otp_roles($conn), true);
}

function sd_login_destination(string $role): ?string
{
    return [
        'patient' => '../patient/dashboard.php',
        'assistant' => '../assistant/dashboard.php',
        'dentist' => '../dentist/dashboard.php',
        'superadmin' => '../superadmin/dashboard.php',
    ][$role] ?? null;
}

/** Starts the role's portal session for an account whose password (and code, when required) was verified. */
function sd_complete_login(mysqli $conn, array $user, string $method): void
{
    $destination = sd_login_destination((string)$user['role']);
    if ($destination === null) {
        destroy_secure_session();
        header('Location: login.php');
        exit;
    }
    $email = strtolower((string)$user['email']);
    $st = $conn->prepare('DELETE FROM login_attempts WHERE email=?');
    $st->bind_param('s', $email);
    $st->execute();
    $st->close();
    // Continue in this role's own portal session (new session id), so signing in here does not
    // sign out the other portals open in this browser.
    switch_to_portal_session((string)$user['role']);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_at'] = time();
    $_SESSION['session_regenerated_at'] = time();
    $_SESSION['auth_fp'] = sd_auth_fingerprint((string)$user['password']);
    $id = (int)$user['id'];
    $st = $conn->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?');
    if ($st) { $st->bind_param('i', $id); $st->execute(); $st->close(); }
    log_activity($conn, $id, 'Signed in', 'Authentication', 'Role: ' . $user['role'] . ' · ' . $method);
    header('Location: ' . $destination);
    exit;
}
