<?php
/**
 * Shared SupremeDent web security helpers.
 *
 * Important: URL paths are not a security boundary. Every protected page must
 * validate the signed-in user and role on the server before doing any work.
 */
const SUPREMEDENT_SESSION_IDLE_TIMEOUT = 1800; // 30 minutes
const SUPREMEDENT_SESSION_REGENERATE_INTERVAL = 900; // 15 minutes

function apply_security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function start_secure_session(): void
{
    apply_security_headers();
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // Each portal keeps its own session cookie so patient, assistant, dentist and super admin
    // can all be signed in at the same time in one browser (see request_portal() below).
    session_name(portal_session_name(request_portal()));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/*
 * One login per portal: patient, assistant, dentist and super admin each use their own session cookie
 * (SUPREMEDENT_PATIENT, SUPREMEDENT_ASSISTANT, SUPREMEDENT_DENTIST, SUPREMEDENT_SUPERADMIN). Signing in to
 * one portal no longer replaces another portal's sign-in, so no tab shows "Access denied".
 * Public and sign-in pages keep the general SUPREMEDENTSESSID session.
 */
const SUPREMEDENT_PORTALS = ['patient', 'assistant', 'dentist', 'superadmin'];

function portal_session_name(string $portal): string
{
    return in_array($portal, SUPREMEDENT_PORTALS, true) ? 'SUPREMEDENT_' . strtoupper($portal) : 'SUPREMEDENTSESSID';
}

/**
 * Portal whose session this request uses: pages inside patient/, assistant/, dentist/ or superadmin/
 * use that folder's portal. Shared api/ endpoints and auth/logout.php use ?portal= (or the portal page
 * that called them). Everything else uses the general session. require_portal_role() still checks the
 * signed-in user's role in the database, so the portal choice never grants access by itself.
 */
function request_portal(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $dir = basename(dirname($script));
    if (in_array($dir, SUPREMEDENT_PORTALS, true)) return $dir;
    if ($dir === 'api' || basename($script) === 'logout.php') {
        foreach ([$_GET['portal'] ?? null, $_POST['portal'] ?? null] as $p) {
            if (is_string($p) && in_array($p, SUPREMEDENT_PORTALS, true)) return $p;
        }
        $ref = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''));
        $sameHost = is_array($ref) && isset($ref['host']) && strcasecmp($ref['host'] . (isset($ref['port']) ? ':' . $ref['port'] : ''), (string)($_SERVER['HTTP_HOST'] ?? '')) === 0;
        if ($sameHost) {
            $refDir = basename(dirname((string)($ref['path'] ?? '')));
            if (in_array($refDir, SUPREMEDENT_PORTALS, true)) return $refDir;
        }
    }
    return '';
}

/**
 * Called by the login page after the password is accepted: continues in the signed-in role's own portal
 * session (new session id). Other portals signed in on this browser are not touched.
 */
function switch_to_portal_session(string $portal): void
{
    if (!in_array($portal, SUPREMEDENT_PORTALS, true)) { session_regenerate_id(true); return; }
    $name = portal_session_name($portal);
    if (session_status() !== PHP_SESSION_ACTIVE) start_secure_session();
    if (session_name() !== $name) {
        session_write_close();
        session_name($name);
        $existing = $_COOKIE[$name] ?? '';
        session_id(is_string($existing) && preg_match('/^[A-Za-z0-9,-]{22,256}$/', $existing) ? $existing : session_create_id());
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION = [];
}

/** Signs out of every portal on this browser (used when logout cannot tell which portal it came from). */
function destroy_all_portal_sessions(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    foreach (SUPREMEDENT_PORTALS as $portal) {
        $name = portal_session_name($portal);
        $id = $_COOKIE[$name] ?? '';
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9,-]{22,256}$/', $id)) continue;
        session_name($name);
        session_id($id);
        session_start();
        destroy_secure_session();
    }
}

function destroy_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) start_secure_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }
    session_destroy();
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid(?string $token): bool
{
    start_secure_session();
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function portal_login_redirect(bool $json = false): void
{
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Authentication required.']);
        exit;
    }
    header('Location: ../auth/login.php');
    exit;
}

function portal_access_denied(bool $json = false): void
{
    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Access denied.']);
        exit;
    }

    http_response_code(403);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Access Denied | SupremeDent</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;margin:0;display:grid;place-items:center;min-height:100vh}.box{max-width:520px;background:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 35px rgba(0,0,0,.08);text-align:center}.box h1{margin:0 0 12px}.box p{line-height:1.6;color:#5b6475}.box a{display:inline-block;margin-top:10px;padding:11px 18px;border-radius:10px;background:#1769e0;color:#fff;text-decoration:none}</style></head><body><div class="box"><h1>Access denied</h1><p>Your signed-in account is not allowed to open this page. Changing the URL does not change your account permissions.</p><a href="../auth/login.php">Return to login</a></div></body></html>';
    exit;
}

/**
 * Server-side role guard for every protected portal page.
 * It re-checks the current user in MySQL, so manually changing a URL or a
 * client-side value cannot grant another role's access.
 */
function require_portal_role(mysqli $conn, $allowedRoles, bool $json = false): void
{
    start_secure_session();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId < 1) portal_login_redirect($json);

    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > SUPREMEDENT_SESSION_IDLE_TIMEOUT) {
        destroy_secure_session();
        portal_login_redirect($json);
    }

    $stmt = $conn->prepare('SELECT role,status,first_name,last_name,email FROM users WHERE id=? LIMIT 1');
    if (!$stmt) portal_access_denied($json);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || ($user['status'] ?? '') !== 'active') {
        destroy_secure_session();
        portal_login_redirect($json);
    }

    // Always trust the role stored in the database, not a role value supplied by the browser.
    $databaseRole = (string)$user['role'];
    $_SESSION['role'] = $databaseRole;
    $_SESSION['first_name'] = (string)$user['first_name'];
    $_SESSION['last_name'] = (string)$user['last_name'];
    $_SESSION['email'] = (string)$user['email'];

    if (!in_array($databaseRole, $allowedRoles, true)) {
        if (function_exists('log_activity')) {
            @log_activity($conn, $userId, 'Blocked unauthorized page access', 'Security', 'Requested: '.($_SERVER['REQUEST_URI'] ?? 'unknown'));
        }
        portal_access_denied($json);
    }

    $regeneratedAt = (int)($_SESSION['session_regenerated_at'] ?? 0);
    if ($regeneratedAt === 0 || (time() - $regeneratedAt) >= SUPREMEDENT_SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated_at'] = time();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Stricter email validation for the public/authentication forms.
 * In addition to PHP's email validation, this catches common typing mistakes
 * such as gmail.com.com and malformed domain labels.
 */
function valid_email_address(string $email): bool
{
    $email = trim($email);
    if ($email === '' || strlen($email) > 150 || substr_count($email, '@') !== 1) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    [$local, $domain] = explode('@', $email, 2);
    $domain = strtolower($domain);
    if ($local === '' || strlen($local) > 64 || str_contains($local, '..') || str_contains($domain, '..')) return false;
    if (!preg_match('/^(?=.{3,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i', $domain)) return false;

    // Repeated common TLDs are almost always a typo: example.com.com.
    if (preg_match('/\.(com|net|org|edu|gov|ph)\.\1$/i', $domain)) return false;

    // Catch common provider typos while still allowing normal custom domains.
    $providerDomains = [
        'gmail.' => ['gmail.com'],
        'googlemail.' => ['googlemail.com'],
        'outlook.' => ['outlook.com'],
        'hotmail.' => ['hotmail.com'],
        'icloud.' => ['icloud.com'],
    ];
    foreach ($providerDomains as $prefix => $allowed) {
        if (str_starts_with($domain, $prefix) && !in_array($domain, $allowed, true)) return false;
    }

    return true;
}

function valid_strong_password(string $password): bool
{
    if (strlen($password) < 8 || strlen($password) > 128) return false;
    return (bool)(preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password));
}
