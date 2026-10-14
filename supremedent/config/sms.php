<?php
/**
 * SupremeDent SMS OTP helper.
 *
 * Default provider: IPROG SMS (https://sms.iprogtech.com) using the clinic's API token.
 * Infobip is still supported by setting 'provider' => 'infobip'.
 *
 * Settings live in config/sms.local.php (gitignored) or in environment variables:
 *   SUPREMEDENT_SMS_PROVIDER      iprog | infobip
 *   SUPREMEDENT_IPROG_API_TOKEN   IPROG "Main API token"
 *   SUPREMEDENT_INFOBIP_API_KEY / SUPREMEDENT_INFOBIP_BASE_URL / SUPREMEDENT_INFOBIP_SENDER
 *   SUPREMEDENT_SMS_LOG_ONLY      1 = write codes to storage/otp.log instead of sending (testing only)
 */

function supremeDentSmsConfig(): array
{
    $config = [
        'provider' => trim((string)(getenv('SUPREMEDENT_SMS_PROVIDER') ?: 'iprog')),
        'iprog_api_token' => trim((string)(getenv('SUPREMEDENT_IPROG_API_TOKEN') ?: '')),
        'iprog_url' => 'https://sms.iprogtech.com/api/v1/sms_messages',
        'api_key' => trim((string)(getenv('SUPREMEDENT_INFOBIP_API_KEY') ?: '')),
        'base_url' => trim((string)(getenv('SUPREMEDENT_INFOBIP_BASE_URL') ?: 'api.infobip.com')),
        'sender' => trim((string)(getenv('SUPREMEDENT_INFOBIP_SENDER') ?: 'ServiceSMS')),
        // Testing mode: write SMS codes to storage/otp.log instead of sending a real SMS.
        'log_only' => filter_var(getenv('SUPREMEDENT_SMS_LOG_ONLY') ?: '0', FILTER_VALIDATE_BOOLEAN),
    ];

    $localFile = __DIR__ . '/sms.local.php';
    if (is_file($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            foreach (['provider', 'iprog_api_token', 'iprog_url', 'api_key', 'base_url', 'sender'] as $key) {
                if (array_key_exists($key, $local) && trim((string)$local[$key]) !== '') {
                    $config[$key] = trim((string)$local[$key]);
                }
            }
            if (array_key_exists('log_only', $local)) {
                $config['log_only'] = filter_var($local['log_only'], FILTER_VALIDATE_BOOLEAN);
            }
        }
    }
    $config['provider'] = strtolower($config['provider']) === 'infobip' ? 'infobip' : 'iprog';

    // Accept either "xxxxx.api.infobip.com" or a full https:// URL.
    if ($config['base_url'] !== '' && !preg_match('#^https?://#i', $config['base_url'])) {
        $config['base_url'] = 'https://' . $config['base_url'];
    }
    $config['base_url'] = rtrim($config['base_url'], '/');

    return $config;
}

function supremeDentNormalizePhone(string $phone): ?array
{
    $digits = preg_replace('/\D+/', '', trim($phone));
    if ($digits === null) return null;

    if (preg_match('/^09\d{9}$/', $digits)) {
        return ['local' => $digits, 'api' => '63' . substr($digits, 1)];
    }
    if (preg_match('/^639\d{9}$/', $digits)) {
        return ['local' => '0' . substr($digits, 2), 'api' => $digits];
    }
    if (preg_match('/^9\d{9}$/', $digits)) {
        return ['local' => '0' . $digits, 'api' => '63' . $digits];
    }

    return null;
}

function supremeDentMaskPhone(string $phone): string
{
    $normalized = supremeDentNormalizePhone($phone);
    if (!$normalized) return $phone;
    $local = $normalized['local'];
    return substr($local, 0, 4) . ' *** ' . substr($local, -4);
}

/**
 * Temporary OTP delivery for local testing: appends the code to storage/otp.log
 * (open it in VS Code) instead of sending an SMS. Turn off with 'log_only' => false.
 */
function supremeDentLogSmsOtp(string $phone, string $otp): array
{
    $normalized = supremeDentNormalizePhone($phone);
    if (!$normalized) {
        return [false, 'Enter a valid Philippine mobile number, for example 09171234567.'];
    }
    if (!preg_match('/^\d{6}$/', $otp)) {
        return [false, 'Unable to create a verification code.'];
    }
    $logFile = __DIR__ . '/../storage/otp.log';
    if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0775, true);
    $line = date('Y-m-d H:i:s') . ' | sms-phone-verify | ' . $normalized['local'] . ' | OTP=' . $otp;
    if (@file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        return [false, 'Could not write the temporary OTP to storage/otp.log. Check the folder permissions.'];
    }
    return [true, 'Temporary OTP saved to storage/otp.log.', [
        'message_id' => 'local-log',
        'status' => 'LOGGED',
        'phone' => $normalized['local'],
        'provider' => 'otp.log',
    ]];
}

/**
 * Extract the human-readable message from an Infobip API error response.
 */
function supremeDentInfobipErrorMessage($data): string
{
    if (!is_array($data)) return '';

    $serviceException = $data['requestError']['serviceException'] ?? null;
    if (is_array($serviceException)) {
        $text = trim((string)($serviceException['text'] ?? ''));
        if ($text !== '') return $text;
        $messageId = trim((string)($serviceException['messageId'] ?? ''));
        if ($messageId !== '') return $messageId;
    }

    foreach (['message', 'error', 'description'] as $key) {
        if (!empty($data[$key]) && is_scalar($data[$key])) {
            return trim((string)$data[$key]);
        }
    }

    return '';
}

/**
 * Send the SupremeDent-generated 6-digit OTP by SMS.
 *
 * Verification of the OTP itself remains on the SupremeDent server/database;
 * the SMS provider (IPROG by default) only delivers the text message.
 */
function supremeDentSendSmsOtp(string $phone, string $otp): array
{
    $config = supremeDentSmsConfig();

    if (!empty($config['log_only'])) {
        return supremeDentLogSmsOtp($phone, $otp);
    }
    return $config['provider'] === 'infobip'
        ? supremeDentSendInfobipOtp($config, $phone, $otp)
        : supremeDentSendIprogOtp($config, $phone, $otp);
}

/** IPROG SMS: POST {api_token, phone_number, message} to /api/v1/sms_messages. */
function supremeDentSendIprogOtp(array $config, string $phone, string $otp): array
{
    $token = trim((string)$config['iprog_api_token']);
    if ($token === '' || stripos($token, 'PASTE_') === 0) {
        return [false, 'SMS is not configured yet. Add the IPROG API token in config/sms.local.php.'];
    }
    $normalized = supremeDentNormalizePhone($phone);
    if (!$normalized) {
        return [false, 'Enter a valid Philippine mobile number, for example 09171234567.'];
    }
    if (!preg_match('/^\d{6}$/', $otp)) {
        return [false, 'Unable to create a verification code.'];
    }
    if (!function_exists('curl_init')) {
        return [false, 'PHP cURL is disabled. Enable the curl extension in XAMPP php.ini and restart Apache.'];
    }

    $payload = json_encode([
        'api_token' => $token,
        'phone_number' => $normalized['api'],
        'message' => 'Your SupremeDent verification code is ' . $otp . '. It expires in 5 minutes. Do not share this code with anyone.',
    ], JSON_UNESCAPED_SLASHES);

    $ch = curl_init((string)$config['iprog_url']);
    $options = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    $caFile = __DIR__ . '/cacert.pem';
    if (is_file($caFile) && filesize($caFile) > 0) {
        $options[CURLOPT_CAINFO] = $caFile;
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $curlError !== '') {
        error_log('[SupremeDent IPROG SMS] cURL error: ' . $curlError);
        return [false, 'Could not connect to the SMS service. Check your internet connection and XAMPP cURL/SSL setup.'];
    }
    $data = json_decode((string)$body, true);
    $status = is_array($data) ? (int)($data['status'] ?? $httpCode) : $httpCode;
    $providerMessage = '';
    if (is_array($data)) {
        $m = $data['message'] ?? ($data['error'] ?? '');
        $providerMessage = is_array($m) ? implode(' ', array_map('strval', $m)) : trim((string)$m);
    }
    if ($httpCode < 200 || $httpCode >= 300 || $status >= 400 || (is_array($data) && isset($data['success']) && !$data['success'])) {
        error_log('[SupremeDent IPROG SMS] HTTP ' . $httpCode . ': ' . substr((string)$body, 0, 1500));
        if ($httpCode === 401 || $status === 401) {
            return [false, 'The SMS service rejected the API token. Check the IPROG API token in config/sms.local.php.'];
        }
        return [false, 'The SMS could not be sent' . ($providerMessage !== '' ? ': ' . $providerMessage : '.') . ' Please try again later.'];
    }

    return [true, 'OTP sent to ' . supremeDentMaskPhone($normalized['local']) . '.', [
        'message_id' => (string)($data['message_id'] ?? $data['data']['message_id'] ?? ''),
        'status' => (string)($data['message_status'] ?? $providerMessage),
        'phone' => $normalized['local'],
        'provider' => 'iprog',
    ]];
}

/** Infobip SMS (optional provider). */
function supremeDentSendInfobipOtp(array $config, string $phone, string $otp): array
{
    $apiKey = trim((string)$config['api_key']);
    if ($apiKey === '' || stripos($apiKey, 'PASTE_') === 0 || stripos($apiKey, 'YOUR_') === 0) {
        return [false, 'Infobip is not configured yet. Add your Infobip API key in config/sms.local.php.'];
    }

    $baseUrl = trim((string)$config['base_url']);
    if ($baseUrl === '') {
        return [false, 'Infobip Base URL is missing in config/sms.local.php.'];
    }

    $sender = trim((string)$config['sender']);
    if ($sender === '') {
        $sender = 'ServiceSMS';
    }

    $normalized = supremeDentNormalizePhone($phone);
    if (!$normalized) {
        return [false, 'Enter a valid Philippine mobile number, for example 09171234567.'];
    }
    if (!preg_match('/^\d{6}$/', $otp)) {
        return [false, 'Unable to create a verification code.'];
    }
    if (!function_exists('curl_init')) {
        return [false, 'PHP cURL is disabled. Enable the curl extension in XAMPP php.ini and restart Apache.'];
    }

    $payload = [
        'messages' => [[
            'sender' => $sender,
            'destinations' => [[
                'to' => $normalized['api'],
            ]],
            'content' => [
                'text' => 'Your SupremeDent verification code is ' . $otp . '. It expires in 5 minutes. Do not share this code with anyone.',
            ],
        ]],
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        return [false, 'Unable to prepare the SMS request.'];
    }

    $ch = curl_init($baseUrl . '/sms/3/messages');
    $options = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => [
            'Authorization: App ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    $caFile = __DIR__ . '/cacert.pem';
    if (is_file($caFile) && filesize($caFile) > 0) {
        $options[CURLOPT_CAINFO] = $caFile;
    }
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $curlError !== '') {
        error_log('[SupremeDent Infobip SMS] cURL error: ' . $curlError);
        return [false, 'Could not connect to Infobip. Check your internet connection and XAMPP cURL/SSL setup.'];
    }

    $data = json_decode((string)$body, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[SupremeDent Infobip SMS] HTTP ' . $httpCode . ': ' . substr((string)$body, 0, 1500));
        $providerMessage = supremeDentInfobipErrorMessage($data);

        if ($httpCode === 401) {
            return [false, 'Infobip rejected the API key. Check the API key in config/sms.local.php.'];
        }
        if ($httpCode === 403) {
            return [false, 'Infobip denied this SMS request. Make sure the API key has the SMS send permission and your trial recipient is verified.'];
        }
        if ($httpCode === 429) {
            return [false, 'Infobip rate limit reached. Please wait a moment and try again.'];
        }

        $message = $providerMessage !== ''
            ? 'Infobip SMS error: ' . $providerMessage
            : 'Infobip rejected the SMS request.';
        return [false, $message . ' On a free trial, SMS can only be sent to a verified recipient number.'];
    }

    $row = is_array($data) && isset($data['messages'][0]) && is_array($data['messages'][0])
        ? $data['messages'][0]
        : null;

    if (!$row || empty($row['messageId'])) {
        error_log('[SupremeDent Infobip SMS] Unexpected response: ' . substr((string)$body, 0, 1500));
        return [false, 'Infobip returned an unexpected response. Check the API transaction log in your Infobip account.'];
    }

    $status = is_array($row['status'] ?? null) ? $row['status'] : [];
    $groupName = strtoupper(trim((string)($status['groupName'] ?? '')));
    $statusName = strtoupper(trim((string)($status['name'] ?? '')));

    if (in_array($groupName, ['REJECTED', 'UNDELIVERABLE', 'EXPIRED'], true) || str_starts_with($statusName, 'REJECTED_')) {
        $description = trim((string)($status['description'] ?? ''));
        return [false, $description !== ''
            ? 'Infobip could not send the SMS: ' . $description
            : 'Infobip could not send the SMS. Check the recipient and your trial account settings.'];
    }

    return [true, 'OTP sent to ' . supremeDentMaskPhone($normalized['local']) . '.', [
        'message_id' => (string)$row['messageId'],
        'status' => (string)($status['name'] ?? $status['groupName'] ?? ''),
        'phone' => $normalized['local'],
        'provider' => 'infobip',
    ]];
}
