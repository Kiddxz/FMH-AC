<?php
/**
 * Settings for the Super Admin backup tool (superadmin_setup/index.php).
 * This file only returns settings; it never prints anything, and .htaccess blocks it in the browser.
 * Keep the setup key secret: do not post it in group chats, screenshots or a public repository.
 */
return [
    // Secret key needed to create a Super Admin. Use at least 24 random characters.
    // Leave it empty ('') to lock the tool when you are not using it.
    'setup_key' => '',

    // Computers allowed to open the tool. Keep this to the server computer itself.
    'allowed_ips' => ['127.0.0.1', '::1'],

    // true = the new Super Admin's email must be confirmed with a 6-digit code (needs Gmail OTP working).
    // Set to false only if Gmail is not configured yet.
    'email_verification' => true,
];
