<?php
// Legacy route kept for compatibility with older SupremeDent links.
$purpose = $_GET['purpose'] ?? 'signup';
if ($purpose === 'reset') {
    header('Location: forgot_password.php');
} else {
    header('Location: signup.php');
}
exit;
