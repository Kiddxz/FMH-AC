<?php
/**
 * Shared SupremeDent web security helpers.
 *
 * Important: URL paths are not a security boundary. Every protected page must
 * validate the signed-in user and role on the server before doing any work.
 */
const SUPREMEDENT_SESSION_IDLE_TIMEOUT = 1800; // 30 minutes
const SUPREMEDENT_SESSION_REGENERATE_INTERVAL = 900; // 15 minutes
const SUPREMEDENT_SESSION_ABSOLUTE_TIMEOUT = 43200; // 12 hours: sign in again at least twice a day

/**
 * Private settings kept outside version control in config/security.local.php
 * (copy config/security.local.php.example). Missing keys fall back to these safe defaults.
 */
function sd_local_config(): array
{
    static $config = null;
    if ($config !== null) return $config;
    $config = [
        'debug' => false,                         // true shows PHP errors on screen (local development only)
        'setup_key' => '',                        // secret needed by superadmin_setup/ (24+ characters)
        'setup_allowed_ips' => ['127.0.0.1', '::1'],
        'setup_email_otp' => true,                // superadmin_setup/ verifies the new admin's email by OTP
        'disable_login_otp' => false,             // emergency switch for the staff sign-in code
    ];
    $file = __DIR__ . '/security.local.php';
    if (is_file($file)) {
        $local = require $file;
        if (is_array($local)) $config = array_replace($config, array_intersect_key($local, $config));
    }
    return $config;
}

// Errors go to the PHP error log, never to the browser, unless debug is switched on locally.
if (!sd_local_config()['debug']) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

function sd_request_is_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function apply_security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    // Blocks <base> hijacking, plugins, framing by other sites and forms that post to other sites.
    header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'");
    if (sd_request_is_https()) header('Strict-Transport-Security: max-age=31536000');
}

function start_secure_session(): void
{
    apply_security_headers();
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');

    $https = sd_request_is_https();
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

/** CSRF token sent with this request: a form field, or the X-CSRF-Token header used by fetch(). */
function csrf_request_token(): ?string
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    return is_string($token) ? $token : null;
}

function sd_request_wants_json(): bool
{
    $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $type = (string)($_SERVER['CONTENT_TYPE'] ?? '');
    return stripos($accept, 'application/json') !== false || stripos($type, 'application/json') !== false
        || ($_POST['ajax'] ?? '') === '1' || isset($_SERVER['HTTP_X_CSRF_TOKEN']);
}

function sd_reject_csrf(bool $json = false): void
{
    http_response_code(419);
    header('Cache-Control: no-store');
    if ($json || sd_request_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'success' => false, 'message' => 'Your session expired. Refresh the page and try again.']);
        exit;
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Session Expired | SupremeDent</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;margin:0;display:grid;place-items:center;min-height:100vh}.box{max-width:520px;background:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 35px rgba(0,0,0,.08);text-align:center}.box p{line-height:1.6;color:#5b6475}.box a{display:inline-block;margin-top:10px;padding:11px 18px;border-radius:10px;background:#0d5f68;color:#fff;text-decoration:none}</style></head>';
    echo '<body><div class="box"><h1>Session expired</h1><p>For your security this request was not saved because its security token was missing or out of date. Go back, refresh the page and try again.</p><a href="javascript:history.back()">Go back</a></div></body></html>';
    exit;
}

/**
 * Adds the CSRF token to every POST form and every same-origin fetch() on portal pages, so older
 * pages are protected too. require_portal_role() rejects POST requests without a valid token.
 */
function sd_enable_csrf_injection(): void
{
    static $enabled = false;
    if ($enabled || PHP_SAPI === 'cli') return;
    $enabled = true;
    $token = csrf_token();
    ob_start(static function (string $html) use ($token): string {
        foreach (headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0 && stripos($header, 'text/html') === false) return $html;
        }
        $safe = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $field = '<input type="hidden" name="csrf_token" value="' . $safe . '">';
        $html = preg_replace_callback('/<form\b(?=[^>]*\bmethod\s*=\s*["\']?post\b)[^>]*>/i', static fn(array $m) => $m[0] . $field, $html) ?? $html;
        $shim = '<meta name="csrf-token" content="' . $safe . '"><script>(function(){var t=' . json_encode($token) . ',f=window.fetch;if(!f)return;'
            . 'window.fetch=function(i,o){try{o=o||{};var m=String(o.method||(i&&i.method)||"GET").toUpperCase(),u=new URL(typeof i==="string"?i:i.url,location.href);'
            . 'if(m!=="GET"&&m!=="HEAD"&&u.origin===location.origin){var h=new Headers(o.headers||(typeof i!=="string"&&i.headers)||{});if(!h.has("X-CSRF-Token"))h.set("X-CSRF-Token",t);o.headers=h;}}catch(e){}return f.call(this,i,o);};})();</script>';
        return preg_replace_callback('#</head>#i', static fn(array $m) => $shim . $m[0], $html, 1) ?? $html;
    });
}

/*
 * Simple fixed-window rate limiter backed by the rate_limits table (brute force, spam and enumeration protection).
 * sd_rate_limited() only checks; sd_rate_hit() counts one more event.
 */
function sd_rate_limited(mysqli $conn, string $bucket, int $max, int $windowSeconds): bool
{
    $bucket = substr($bucket, 0, 190);
    $st = $conn->prepare('SELECT hits, window_start FROM rate_limits WHERE bucket=? LIMIT 1');
    if (!$st) return false;
    $st->bind_param('s', $bucket);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row || (int)$row['window_start'] + $windowSeconds <= time()) return false;
    return (int)$row['hits'] >= $max;
}

function sd_rate_hit(mysqli $conn, string $bucket, int $windowSeconds): void
{
    $bucket = substr($bucket, 0, 190);
    $now = time();
    $expired = $now - $windowSeconds;
    $st = $conn->prepare('INSERT INTO rate_limits(bucket,hits,window_start) VALUES(?,1,?) ON DUPLICATE KEY UPDATE hits=IF(window_start<=?,1,hits+1), window_start=IF(window_start<=?,VALUES(window_start),window_start)');
    if (!$st) return;
    $st->bind_param('siii', $bucket, $now, $expired, $expired);
    $st->execute();
    $st->close();
}

/** Check and count in one step. Returns false when the limit is already reached. */
function sd_rate_allow(mysqli $conn, string $bucket, int $max, int $windowSeconds): bool
{
    if (sd_rate_limited($conn, $bucket, $max, $windowSeconds)) return false;
    sd_rate_hit($conn, $bucket, $windowSeconds);
    return true;
}

function sd_rate_clear(mysqli $conn, string $bucket): void
{
    $bucket = substr($bucket, 0, 190);
    $st = $conn->prepare('DELETE FROM rate_limits WHERE bucket=?');
    if (!$st) return;
    $st->bind_param('s', $bucket);
    $st->execute();
    $st->close();
}

/** Fingerprint of the stored password hash: a password change signs out every other session. */
function sd_auth_fingerprint(string $passwordHash): string
{
    return hash('sha256', 'supremedent-session|' . $passwordHash);
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
    $loginAt = (int)($_SESSION['login_at'] ?? 0);
    if ($loginAt > 0 && (time() - $loginAt) > SUPREMEDENT_SESSION_ABSOLUTE_TIMEOUT) {
        destroy_secure_session();
        portal_login_redirect($json);
    }

    $stmt = $conn->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    if (!$stmt) portal_access_denied($json);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || ($user['status'] ?? '') !== 'active') {
        destroy_secure_session();
        portal_login_redirect($json);
    }

    // A password change or reset ends every other signed-in session of that account.
    $fingerprint = sd_auth_fingerprint((string)$user['password']);
    if (!empty($_SESSION['auth_fp']) && !hash_equals((string)$_SESSION['auth_fp'], $fingerprint)) {
        destroy_secure_session();
        portal_login_redirect($json);
    }
    $_SESSION['auth_fp'] = $fingerprint;

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

    // Every state-changing request from a portal must carry the session's CSRF token.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_valid(csrf_request_token())) {
        sd_reject_csrf($json);
    }

    $regeneratedAt = (int)($_SESSION['session_regenerated_at'] ?? 0);
    if ($regeneratedAt === 0 || (time() - $regeneratedAt) >= SUPREMEDENT_SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated_at'] = time();
    }
    $_SESSION['last_activity'] = time();
    if ($loginAt === 0) $_SESSION['login_at'] = time();

    // Accounts created by the clinic (temporary or admin-set password) must choose their own password first.
    if ((int)($user['must_change_password'] ?? 0) === 1) {
        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'success' => false, 'message' => 'Please change your temporary password first.']);
            exit;
        }
        sd_force_password_change($conn, $user);
    }

    if (!$json) sd_enable_csrf_injection();
}

/**
 * Shown instead of any portal page while the account still uses a temporary password.
 * The user must enter the temporary password once more and choose a new strong password.
 */
function sd_force_password_change(mysqli $conn, array $user): void
{
    $error = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['sd_force_password'] ?? '') === '1') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (!password_verify($current, (string)$user['password'])) {
            usleep(250000);
            $error = 'The temporary password you entered is incorrect.';
        } elseif (!valid_strong_password($new)) {
            $error = 'Use 8-128 characters with uppercase, lowercase, a number and a special character. Avoid common words such as "password".';
        } elseif ($new !== $confirm) {
            $error = 'The new passwords do not match.';
        } elseif (password_verify($new, (string)$user['password'])) {
            $error = 'Choose a password different from the temporary password.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $id = (int)$user['id'];
            $st = $conn->prepare('UPDATE users SET password=?, must_change_password=0, password_changed_at=NOW() WHERE id=?');
            $st->bind_param('si', $hash, $id);
            $st->execute();
            $st->close();
            $_SESSION['auth_fp'] = sd_auth_fingerprint($hash);
            session_regenerate_id(true);
            if (function_exists('log_activity')) log_activity($conn, $id, 'Changed temporary password', 'Authentication', 'First sign-in password change');
            header('Location: ' . basename((string)($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php')));
            exit;
        }
    }
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8');
    $msg = $error !== '' ? '<div class="auth-message is-error" style="display:block" role="alert">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>' : '';
    $logout = '../auth/logout.php?portal=' . rawurlencode(request_portal());
    http_response_code(200);
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change Temporary Password | SupremeDent</title>'
        . '<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css"></head>'
        . '<body><div class="auth-page auth-stacked"><div class="auth-shell"><main class="auth-form-panel"><div class="auth-form-wrap"><div class="auth-wordmark">SUPREMEDENT</div>'
        . '<p class="auth-kicker">Hello ' . $name . ', please set your own password</p><p class="muted">Your account was created by the clinic with a temporary password. For your security, choose a new password before you continue.</p>' . $msg
        . '<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="' . $token . '"><input type="hidden" name="sd_force_password" value="1">'
        . '<div class="auth-field"><label for="sdCur">Temporary password</label><input class="input-field" id="sdCur" type="password" name="current_password" maxlength="128" autocomplete="current-password" required></div>'
        . '<div class="auth-field"><label for="sdNew">New password</label><input class="input-field" id="sdNew" type="password" name="new_password" maxlength="128" autocomplete="new-password" required></div>'
        . '<div class="auth-field"><label for="sdCon">Confirm new password</label><input class="input-field" id="sdCon" type="password" name="confirm_password" maxlength="128" autocomplete="new-password" required></div>'
        . '<div class="validation-list"><span>8+ characters</span><span>Uppercase letter</span><span>Lowercase letter</span><span>Number</span><span>Special character</span></div>'
        . '<button class="btn btn-primary" type="submit">Save New Password</button></form>'
        . '<div class="auth-footer"><a href="' . htmlspecialchars($logout, ENT_QUOTES, 'UTF-8') . '">Log out</a></div></div></main></div></div></body></html>';
    exit;
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
    // Reject the guessable patterns attackers try first, even when they meet the character rules.
    if (preg_match('/pass(word|wd)|qwerty|asdfgh|12345678|87654321|abcd1234|admin123|letmein|welcome1|supremedent|(.)\1{3,}/i', $password)) return false;
    return (bool)(preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password));
}
