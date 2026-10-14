<?php

require_once __DIR__ . '/config/mail.php';

header('Content-Type: text/plain; charset=UTF-8');

$testEmail = 'YOUR_RECEIVER_EMAIL@gmail.com';
$otp = (string) random_int(100000, 999999);

echo "Testing SupremeDent Gmail SMTP...\n\n";

$config = supremeDentMailConfig();

echo "Gmail account: " . $config['gmail_user'] . "\n";
echo "App password loaded: " . ($config['gmail_app_password'] !== '' ? 'YES' : 'NO') . "\n";
echo "App password length: " . strlen($config['gmail_app_password']) . "\n\n";

$result = sendOtpEmail(
    $testEmail,
    'SupremeDent Test',
    $otp,
    'signup'
);

if ($result) {
    echo "SUCCESS\n";
    echo "OTP sent to: " . $testEmail . "\n";
} else {
    echo "FAILED\n";
    echo "Check C:\\xampp\\apache\\logs\\error.log\n";
}