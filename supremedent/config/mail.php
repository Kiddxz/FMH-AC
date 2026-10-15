<?php
/* Shared Gmail SMTP transport for signup, password recovery and the admin test. */
const MAIL_FROM = 'supremedentc@gmail.com';
const MAIL_FROM_NAME = 'SupremeDent Dental Services';
const OTP_LOG_FILE = __DIR__ . '/../storage/otp.log';

function supremeDentMailConfig(): array
{
    $config = ['gmail_user' => MAIL_FROM, 'gmail_app_password' => '',
        'smtp_port' => 'auto', 'smtp_timeout' => 8, 'ca_file' => __DIR__ . '/cacert.pem', 'log_otp' => false,
        // Public address of the site used in emailed links, e.g. https://clinic.example.com/supremedent
        'app_url' => ''];
    $file = __DIR__ . '/mail.local.php';
    if (is_file($file)) {
        $local = require $file;
        if (is_array($local)) $config = array_replace($config, array_intersect_key($local, $config));
    }
    foreach (['gmail_user' => 'SUPREMEDENT_GMAIL_USER', 'gmail_app_password' => 'SUPREMEDENT_GMAIL_APP_PASSWORD',
        'smtp_port' => 'SUPREMEDENT_SMTP_PORT', 'ca_file' => 'SUPREMEDENT_SMTP_CA_FILE'] as $key => $env) {
        $value = getenv($env);
        if ($value !== false && trim($value) !== '') $config[$key] = trim($value);
    }
    $config['gmail_user'] = trim((string)$config['gmail_user']);
    $config['gmail_app_password'] = preg_replace('/\s+/', '', (string)$config['gmail_app_password']);
    $config['smtp_timeout'] = max(3, min(10, (int)$config['smtp_timeout']));
    $config['log_otp'] = filter_var($config['log_otp'], FILTER_VALIDATE_BOOLEAN);
    return $config;
}

function supremeDentMailFail(string $code, string $message, string $detail = ''): bool
{
    // Never store SMTP commands, credentials or message bodies in diagnostics.
    $GLOBALS['supremedent_mail_error'] = ['code' => $code, 'message' => $message,
        'detail' => substr(str_replace(["\r", "\n"], ' ', $detail), 0, 1000)];
    error_log('[SupremeDent Mail] ' . $code . ': ' . $message . ' ' . $GLOBALS['supremedent_mail_error']['detail']);
    return false;
}

function supremeDentMailLastError(): array
{
    return $GLOBALS['supremedent_mail_error'] ?? ['code' => '', 'message' => '', 'detail' => ''];
}

function logOtpForDevelopment(string $email, string $otp, string $purpose): void
{
    if (!supremeDentMailConfig()['log_otp']) return;
    if (!is_dir(dirname(OTP_LOG_FILE))) @mkdir(dirname(OTP_LOG_FILE), 0775, true);
    $line = date('Y-m-d H:i:s') . ' | ' . str_replace(["\r", "\n"], '', $purpose . ' | ' . $email) . ' | OTP=' . $otp;
    @file_put_contents(OTP_LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    error_log('[SupremeDent OTP] ' . $line);
}

function smtpReadResponse($socket): array
{
    $response = '';
    $code = 0;
    for ($i = 0; $i < 100 && ($line = fgets($socket, 1024)) !== false; $i++) {
        $response .= $line;
        if (!preg_match('/^(\d{3})([ -])/', $line, $match)) break;
        $code = (int)$match[1];
        if ($match[2] === ' ') return ['code' => $code, 'response' => trim($response)];
    }
    return ['code' => 0, 'response' => trim($response) ?: 'No SMTP response (connection closed or timed out).'];
}

function smtpWriteAll($socket, string $data): bool
{
    $length = strlen($data);
    for ($offset = 0; $offset < $length; $offset += $written) {
        $written = @fwrite($socket, substr($data, $offset));
        if ($written === false || $written === 0) return supremeDentMailFail('smtp_write', 'The email connection was interrupted. Please try again.');
    }
    return true;
}

function smtpExpect($socket, array $acceptedCodes, string $step): bool
{
    $remaining = (int)ceil(($GLOBALS['supremedent_smtp_deadline'] ?? (microtime(true) + 8)) - microtime(true));
    if ($remaining <= 0) return supremeDentMailFail('smtp_timeout', 'The email service timed out. Please try again.');
    stream_set_timeout($socket, min(8, $remaining));
    $result = smtpReadResponse($socket);
    if (in_array($result['code'], $acceptedCodes, true)) return true;
    if ($step === 'AUTH' || $step === 'USERNAME' || $step === 'PASSWORD') {
        $code = 'gmail_auth';
        $message = 'The clinic email service could not sign in to Gmail. Please contact the clinic.';
    } else {
        $code = 'smtp_rejected';
        $message = 'The email service did not accept the verification email. Please try again later.';
    }
    return supremeDentMailFail($code, $message, $step . ': ' . $result['response']);
}

function smtpCommand($socket, string $command, array $acceptedCodes, string $step): bool
{
    return smtpWriteAll($socket, $command . "\r\n") && smtpExpect($socket, $acceptedCodes, $step);
}

function supremeDentSmtpMessage(string $user, string $to, string $subject, string $body): string
{
    $subject = str_replace(["\r", "\n"], '', $subject);
    $headers = ['Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($user, '@'), 1) . '>',
        'From: ' . MAIL_FROM_NAME . ' <' . $user . '>', 'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=', 'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64'];
    // Base64 keeps body lines safe for SMTP and supports Unicode patient names.
    return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n") . ".\r\n";
}

function sendViaGmailSmtp(string $to, string $subject, string $body): bool
{
    $GLOBALS['supremedent_mail_error'] = ['code' => '', 'message' => '', 'detail' => ''];
    $cfg = supremeDentMailConfig();
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to))
        return supremeDentMailFail('invalid_email', 'Enter a valid email address.');
    if (!filter_var($cfg['gmail_user'], FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $cfg['gmail_user']))
        return supremeDentMailFail('mail_config', 'The clinic email service is not configured. Please contact the clinic.');
    if (!preg_match('/^[a-zA-Z0-9]{16}$/D', $cfg['gmail_app_password']))
        return supremeDentMailFail('mail_config', 'The clinic email service is not configured. Please contact the clinic.', 'Set a 16-character Google App Password in config/mail.local.php.');
    if (!extension_loaded('openssl'))
        return supremeDentMailFail('openssl_missing', 'The clinic email service is unavailable. Please contact the clinic.', 'Enable extension=openssl in XAMPP php.ini and restart Apache.');

    $tls = ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => 'smtp.gmail.com', 'SNI_enabled' => true];
    if ($cfg['ca_file'] !== '') {
        if (!is_readable($cfg['ca_file'])) return supremeDentMailFail('tls_config', 'The clinic email service is unavailable.', 'Configured CA certificate file is not readable.');
        $tls['cafile'] = $cfg['ca_file'];
    }
    $ports = (string)$cfg['smtp_port'] === 'auto' ? [465, 587] : [(int)$cfg['smtp_port']];
    if (array_diff($ports, [465, 587])) return supremeDentMailFail('mail_config', 'The clinic email service is not configured.', 'SMTP port must be auto, 465 or 587.');
    $GLOBALS['supremedent_smtp_deadline'] = microtime(true) + 45;
    $socket = false;
    foreach ($ports as $port) {
        $context = stream_context_create(['ssl' => $tls]);
        $errno = 0; $errstr = '';
        $socket = @stream_socket_client(($port === 465 ? 'ssl' : 'tcp') . '://smtp.gmail.com:' . $port,
            $errno, $errstr, $cfg['smtp_timeout'], STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            supremeDentMailFail('smtp_connection', 'The clinic email service could not connect to Gmail. Please try again later.', 'Port ' . $port . ': ' . $errno . ' ' . $errstr);
            continue;
        }
        stream_set_timeout($socket, $cfg['smtp_timeout']);
        if (!smtpExpect($socket, [220], 'CONNECT') || !smtpCommand($socket, 'EHLO supremedent.local', [250], 'EHLO')) {
            fclose($socket); $socket = false; continue;
        }
        if ($port === 587) {
            if (!smtpCommand($socket, 'STARTTLS', [220], 'STARTTLS') ||
                @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true ||
                !smtpCommand($socket, 'EHLO supremedent.local', [250], 'EHLO TLS')) {
                supremeDentMailFail('smtp_tls', 'The clinic email service could not establish a secure connection.', 'STARTTLS failed. Check the CA certificate configuration in php.ini.');
                fclose($socket); $socket = false; continue;
            }
        }
        break;
    }
    if (!$socket) return false;
    // No retry after authentication or DATA: avoid duplicate delivery on uncertain responses.
    try {
        if (!smtpCommand($socket, 'AUTH LOGIN', [334], 'AUTH') ||
            !smtpCommand($socket, base64_encode($cfg['gmail_user']), [334], 'USERNAME') ||
            !smtpCommand($socket, base64_encode($cfg['gmail_app_password']), [235], 'PASSWORD') ||
            !smtpCommand($socket, 'MAIL FROM:<' . $cfg['gmail_user'] . '>', [250], 'MAIL FROM') ||
            !smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO') ||
            !smtpCommand($socket, 'DATA', [354], 'DATA') ||
            !smtpWriteAll($socket, supremeDentSmtpMessage($cfg['gmail_user'], $to, $subject, $body)) ||
            !smtpExpect($socket, [250], 'SEND MESSAGE')) return false;
        $GLOBALS['supremedent_mail_error'] = ['code' => '', 'message' => '', 'detail' => ''];
        @fwrite($socket, "QUIT\r\n");
        error_log('[SupremeDent Mail] Gmail accepted the verification email.');
        return true;
    } finally {
        fclose($socket);
    }
}

function sendOtpEmail(string $email, string $name, string $otp, string $purpose, string $extra = ''): bool
{
    $subjects = ['signup' => 'SupremeDent Patient Registration Code', 'reset' => 'SupremeDent Password Reset',
        'register' => 'SupremeDent Email Verification Code'];
    $subject = $subjects[$purpose] ?? 'SupremeDent Email Test Code';
    $intro = $purpose === 'reset'
        ? "We received a request to reset the password of your SupremeDent account.\n\nYour password reset code is: {$otp}\n\n"
        : "Your SupremeDent verification code is: {$otp}\n\n";
    $body = "Hello {$name},\n\n" . $intro
        . ($extra !== '' ? $extra . "\n\n" : '')
        . ($purpose === 'test' ? "This is a mail connection test; it cannot be used to sign up or reset a password.\n\n" :
            "This code expires in 10 minutes and can only be used once.\n\n")
        . "If you did not request this, you can ignore this email and your account stays unchanged.\n\nSupremeDent Dental Services";
    logOtpForDevelopment($email, $otp, $purpose);
    return sendViaGmailSmtp($email, $subject, $body);
}

/**
 * Base URL of the SupremeDent folder for links inside emails (no trailing slash), or '' when it
 * cannot be trusted. Uses 'app_url' from config/mail.local.php when set; otherwise the request host
 * is only trusted on local/LAN addresses (XAMPP), so a forged Host header cannot poison reset links.
 */
function supremeDentAppUrl(): string
{
    $configured = trim((string)(supremeDentMailConfig()['app_url'] ?? ''));
    if ($configured !== '' && preg_match('#^https?://#i', $configured)) return rtrim($configured, '/');
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $name = preg_replace('/:\d+$/', '', $host);
    $local = $name === 'localhost' || preg_match('/^(127\.\d+\.\d+\.\d+|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[01])\.\d+\.\d+)$/', $name);
    if (!$local || !preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/', $host)) return '';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    // Scripts live one folder below the site root (auth/, assistant/, patient/ ...).
    $root = rtrim(str_replace('\\', '/', dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/x/x')))), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $root;
}

/** Plain-text email to a patient registered by clinic staff: their sign-in email and a temporary password. */
function sendPatientCredentialsEmail(string $email, string $name, string $password): bool
{
    $url = supremeDentAppUrl();
    $body = "Hello {$name},\n\nYour SupremeDent patient account has been created by the clinic.\n\n"
        . "Username (email): {$email}\nTemporary password: {$password}\n\n"
        . ($url !== '' ? "Sign in here: {$url}/auth/login.php\n\n" : '')
        . "For your security, please sign in and change this password from your profile (Change Password).\n"
        . "Never share your password with anyone.\n\nSupremeDent Dental Services";
    return sendViaGmailSmtp($email, 'Your SupremeDent Patient Account', $body);
}
