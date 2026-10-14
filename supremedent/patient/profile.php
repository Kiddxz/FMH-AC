<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);
require_once __DIR__ . '/../config/sms.php';

$userId = (int) $_SESSION['user_id'];
$message = '';
$messageType = '';

$stmt = $conn->prepare("SELECT first_name,last_name,email FROM users WHERE id=? AND role='patient' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    header('Location: ../auth/logout.php?portal=patient');
    exit;
}

$profile = patient_profile_data($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneInput = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $medicalHistory = trim($_POST['medical_history'] ?? '');
    $dentalHistory = trim($_POST['dental_history'] ?? '');
    $normalizedPhone = supremeDentNormalizePhone($phoneInput);
    $verifiedStoredPhone = !empty($profile['phone_verified_at']) ? supremeDentNormalizePhone((string) ($profile['phone'] ?? '')) : null;

    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Refresh the page and try again.';
        $messageType = 'error';
    } elseif ($firstName === '' || $lastName === '' || $email === '' || $phoneInput === '' || $address === '' || $birthday === '' || $gender === '') {
        $message = 'Please complete all required profile fields before booking an appointment.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (!$normalizedPhone) {
        $message = 'Please enter a valid Philippine mobile number, for example 09171234567.';
        $messageType = 'error';
    } elseif (!$verifiedStoredPhone || $verifiedStoredPhone['local'] !== $normalizedPhone['local']) {
        $message = 'Please verify this contact number by SMS OTP before saving your profile.';
        $messageType = 'error';
    } elseif (strtotime($birthday) === false || strtotime($birthday) > strtotime('-16 years') || strtotime($birthday) < strtotime('1900-01-01')) {
        $message = 'Appointments are available for patients aged 16 or older. Please enter a valid birth date.';
        $messageType = 'error';
    } elseif (!in_array($gender, ['Male', 'Female'], true)) {
        $message = 'Please select a valid gender.';
        $messageType = 'error';
    } else {
        $phone = $normalizedPhone['local'];
        $check = $conn->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
        $check->bind_param('si', $email, $userId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = 'This email is already being used by another account.';
            $messageType = 'error';
        } else {
            $conn->begin_transaction();
            try {
                $st = $conn->prepare('UPDATE users SET first_name=?,last_name=?,email=? WHERE id=?');
                $st->bind_param('sssi', $firstName, $lastName, $email, $userId);
                $st->execute();
                $st->close();

                // phone_verified_at is intentionally preserved because the submitted number
                // must already match the number verified through the SMS endpoint above.
                $st = $conn->prepare("INSERT INTO patient_profiles(user_id,phone,address,birthday,gender,medical_history,dental_history) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE phone=VALUES(phone),address=VALUES(address),birthday=VALUES(birthday),gender=VALUES(gender),medical_history=VALUES(medical_history),dental_history=VALUES(dental_history)");
                $st->bind_param('issssss', $userId, $phone, $address, $birthday, $gender, $medicalHistory, $dentalHistory);
                $st->execute();
                $st->close();

                $conn->commit();
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;
                $user = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];
                $profile = patient_profile_data($conn, $userId);
                $message = 'Profile updated successfully. You can now use all patient booking features.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('[SupremeDent Profile] Update failed: ' . $e->getMessage());
                $message = 'Unable to update your profile. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

$complete = patient_profile_is_complete($conn, $userId);
$phoneIsVerified = trim((string) ($profile['phone'] ?? '')) !== '' && !empty($profile['phone_verified_at']);
$filled = 0;
if ($phoneIsVerified)
    $filled++;
foreach (['address', 'birthday', 'gender'] as $f) {
    if (trim((string) ($profile[$f] ?? '')) !== '')
        $filled++;
}
$requiredCount = 4;
$progress = (int) round(($filled / $requiredCount) * 100);
$age = '';
if (!empty($profile['birthday']) && strtotime($profile['birthday'])) {
    $age = (new DateTime($profile['birthday']))->diff(new DateTime('today'))->y;
}
$verifiedPhoneValue = $phoneIsVerified ? (string) $profile['phone'] : '';

require '../includes/ui.php';
ui_header('My Profile', 'patient');
ui_sidebar('patient', 'profile.php');
?>
<div class="page-head">
    <div>
        <div class="eyebrow">PATIENT PROFILE</div>
        <h1>My Profile</h1>
        <div class="muted">Complete your basic information before requesting an appointment.</div>
    </div><a class="btn btn-secondary" href="change_password.php"><i class="fa-solid fa-key"></i> Change Password</a>
</div>
<?php if ($message)
    flash($messageType, $message); ?>
<?php if (!$complete): ?>
    <div class="profile-required-alert"><i class="fa-solid fa-circle-exclamation"></i>
        <div><strong>Profile incomplete</strong><span>A verified phone number, address, birthday, and gender are required
                before appointment booking is enabled.</span></div>
    </div>
<?php else: ?>
    <div class="profile-complete-alert"><i class="fa-solid fa-circle-check"></i>
        <div><strong>Profile complete</strong><span>Your contact number is verified and appointment booking access is
                enabled.</span></div>
    </div>
<?php endif; ?>

<section class="card profile-progress-card">
    <div>
        <h2>Profile Completion</h2>
        <p class="muted"><?= $filled ?> of <?= $requiredCount ?> required patient details completed</p>
    </div>
    <div class="profile-progress"><span style="width:<?= $progress ?>%"></span></div><b><?= $progress ?>%</b>
</section>

<section class="card">
    <div class="card-heading">
        <div>
            <h2>Patient Information</h2>
            <p class="muted">Fields marked with * are required for appointment booking.</p>
        </div>
    </div>
    <form method="post" class="form-grid fitted-form" id="profileForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field"><label>First Name *</label><input name="first_name" value="<?= e($user['first_name']) ?>"
                required></div>
        <div class="field"><label>Last Name *</label><input name="last_name" value="<?= e($user['last_name']) ?>"
                required></div>
        <div class="field"><label>Email Address *</label><input type="email" name="email" value="<?= e($user['email']) ?>"
                required></div>
        <div class="field">
            <label for="phone">Contact Number *</label>
            <div class="phone-verify-row" id="phoneVerifyRow" data-verified-phone="<?= e($verifiedPhoneValue) ?>">
                <input id="phone" name="phone" value="<?= e($profile['phone'] ?? '') ?>" placeholder="09XX XXX XXXX"
                    inputmode="tel" maxlength="16" required>
                <button class="btn phone-verify-btn <?= $phoneIsVerified ? 'is-verified' : '' ?>" type="button"
                    id="sendPhoneOtp">
                    <i class="fa-solid <?= $phoneIsVerified ? 'fa-circle-check' : 'fa-shield-halved' ?>"></i>
                    <span><?= $phoneIsVerified ? 'Verified' : 'Verify' ?></span>
                </button>
                <span class="phone-green-check <?= $phoneIsVerified ? 'is-visible' : '' ?>" id="phoneGreenCheck"
                    title="Verified contact number" aria-label="Verified contact number"><i
                        class="fa-solid fa-check"></i></span>
            </div>
            <div class="phone-status <?= $phoneIsVerified ? 'success' : '' ?>" id="phoneStatus" role="status"
                aria-live="polite">
                <?= $phoneIsVerified ? 'This contact number is verified by SMS.' : 'Verify your mobile number to enable appointment booking.' ?>
            </div>
        </div>
        <div class="field full"><label>Address *</label><input name="address" value="<?= e($profile['address'] ?? '') ?>"
                placeholder="Complete home address" required></div>
        <div class="field"><label for="birthday">Birthday * <small>(16 years or older)</small></label><input type="date"
                id="birthday" name="birthday" min="1900-01-01" max="<?= date('Y-m-d', strtotime('-16 years')) ?>"
                value="<?= e($profile['birthday'] ?? '') ?>" required>
            <div class="field-hint" id="birthdayHint" aria-live="polite"></div>
        </div>
        <div class="field"><label for="ageDisplay">Age (automatically calculated)</label><input id="ageDisplay"
                value="<?= e($age === '' ? '' : $age . ' years old') ?>" placeholder="Calculated from birthday" readonly
                tabindex="-1"></div>
        <div class="field"><label>Gender *</label><select name="gender" required>
                <option value="">Select</option><?php foreach (['Male', 'Female'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select></div>

        <div class="actions full refined-profile-actions"><span class="muted">Review your information before
                saving.</span><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save
                Profile</button><?php if ($complete): ?><a class="btn btn-secondary" href="appointments.php"><i
                        class="fa-solid fa-calendar-plus"></i> Book Appointment</a><?php endif; ?></div>
    </form>
</section>

<div class="sms-otp-modal" id="smsOtpModal" aria-hidden="true">
    <div class="sms-otp-dialog" role="dialog" aria-modal="true" aria-labelledby="smsOtpTitle">
        <button type="button" class="sms-modal-close" id="closeSmsModal" aria-label="Close"><i
                class="fa-solid fa-xmark"></i></button>
        <div class="sms-modal-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <h2 id="smsOtpTitle">Verify Contact Number</h2>
        <p>We sent a 6-digit OTP to <strong
                id="otpPhoneLabel"><?= e(supremeDentMaskPhone((string) ($profile['phone'] ?? ''))) ?></strong>.</p>
        <div class="sms-otp-input-wrap">
            <label id="smsOtpLabel">Enter SMS OTP</label>
            <div class="otp-boxes" id="otpBoxes" role="group" aria-labelledby="smsOtpLabel">
                <?php for ($i = 0; $i < 6; $i++): ?><input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>" aria-label="Digit <?= $i + 1 ?>"><?php endfor; ?>
            </div>
        </div>
        <div class="sms-modal-message" id="smsModalMessage" role="status" aria-live="polite"></div>
        <p class="sms-resend-line">Didn't get the code? <button type="button" class="sms-resend" id="resendSmsOtp">Resend code</button></p>
        <small class="sms-expiry-note">The code is checked automatically once all 6 digits are entered. It expires in 5 minutes.</small>
    </div>
</div>

<style>
    .profile-required-alert,
    .profile-complete-alert {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        padding: 15px 17px;
        border-radius: 14px;
        margin-bottom: 18px;
        border: 1px solid
    }

    .profile-required-alert {
        background: #fff7e7;
        border-color: #f0d69a;
        color: #755416
    }

    .profile-complete-alert {
        background: #eaf7ef;
        border-color: #c7e8d3;
        color: #216b42
    }

    .profile-required-alert i,
    .profile-complete-alert i {
        margin-top: 3px;
        font-size: 18px
    }

    .profile-required-alert span,
    .profile-complete-alert span {
        display: block;
        margin-top: 3px;
        font-size: 13px
    }

    .profile-progress-card {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(220px, 2fr) auto;
        align-items: center;
        gap: 18px
    }

    .profile-progress {
        height: 12px;
        background: #edf3f4;
        border-radius: 999px;
        overflow: hidden
    }

    .profile-progress span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #0d6971, #4aa78e);
        border-radius: 999px
    }

    .profile-progress-card b {
        font-size: 22px
    }

    .phone-verify-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 112px 36px;
        gap: 8px;
        align-items: center
    }

    .phone-verify-btn {
        min-height: 44px !important;
        background: #16858c;
        color: #fff;
        gap: 7px
    }

    .phone-verify-btn.is-verified {
        background: #e7f7ed !important;
        color: #1b7a46 !important;
        box-shadow: none !important;
        border: 1px solid #bde2c9
    }

    .phone-green-check {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #d7f3e1;
        color: #16804a;
        opacity: 0;
        transform: scale(.7);
        transition: .18s ease
    }

    .phone-green-check.is-visible {
        opacity: 1;
        transform: scale(1)
    }

    .phone-status {
        min-height: 18px;
        font-size: 12px;
        color: #708086
    }

    .phone-status.success {
        color: #1d7a48;
        font-weight: 700
    }

    .phone-status.error {
        color: #b43b3b;
        font-weight: 700
    }

    .sms-otp-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 3000;
        background: rgba(13, 42, 50, .62);
        backdrop-filter: blur(4px);
        padding: 20px;
        align-items: center;
        justify-content: center
    }

    .sms-otp-modal.is-open {
        display: flex
    }

    .sms-otp-dialog {
        position: relative;
        width: min(440px, 100%);
        padding: 32px;
        background: #fff;
        border: 1px solid #dce9e8;
        border-radius: 22px;
        box-shadow: 0 28px 80px rgba(11, 44, 50, .3);
        text-align: center
    }

    .sms-modal-close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 50%;
        background: #edf5f4;
        color: #456267;
        cursor: pointer
    }

    .sms-modal-icon {
        width: 62px;
        height: 62px;
        margin: 0 auto 15px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        background: #e7f5f4;
        color: #0d747a;
        font-size: 27px
    }

    .sms-otp-dialog h2 {
        margin: 0 0 8px;
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 24px
    }

    .sms-otp-dialog>p {
        margin: 0 auto 20px;
        color: #5d7478;
        line-height: 1.6
    }

    .sms-otp-input-wrap {
        text-align: left
    }

    .sms-otp-input-wrap label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 750
    }

    .sms-otp-input-wrap input {
        width: 100%;
        padding: 14px;
        border: 1px solid #ccdcdd;
        border-radius: 12px;
        font: 700 22px/1 'DM Sans', sans-serif;
        letter-spacing: .35em;
        text-align: center;
        outline: none
    }

    .sms-otp-input-wrap input:focus {
        border-color: #0d747a;
        box-shadow: 0 0 0 3px rgba(13, 116, 122, .12)
    }

    .sms-modal-message {
        min-height: 38px;
        padding: 10px 0 4px;
        font-size: 13px;
        color: #637b7f
    }

    .sms-modal-message.success {
        color: #1e7b48;
        font-weight: 700
    }

    .sms-modal-message.error {
        color: #b43b3b;
        font-weight: 700
    }

    .sms-verify-submit {
        width: 100% !important;
        margin-top: 4px
    }

    .sms-resend {
        margin: 14px auto 5px;
        border: 0;
        background: transparent;
        color: #0d747a;
        font: 700 13px 'DM Sans', sans-serif;
        cursor: pointer
    }

    .sms-resend:disabled {
        color: #94a5a7;
        cursor: not-allowed
    }

    .sms-expiry-note {
        display: block;
        margin-top: 12px;
        color: #849497;
        line-height: 1.5
    }

    @media(max-width:680px) {
        .profile-progress-card {
            grid-template-columns: 1fr
        }

        .profile-progress-card b {
            justify-self: end
        }

        .phone-verify-row {
            grid-template-columns: minmax(0, 1fr) 96px 32px
        }

        .phone-verify-btn {
            padding: 9px !important
        }

        .phone-verify-btn i {
            display: none
        }

        .sms-otp-dialog {
            padding: 28px 20px
        }
    }
</style>

<script>
    (() => {
        const phone = document.getElementById('phone');
        const row = document.getElementById('phoneVerifyRow');
        const sendBtn = document.getElementById('sendPhoneOtp');
        const check = document.getElementById('phoneGreenCheck');
        const status = document.getElementById('phoneStatus');
        const modal = document.getElementById('smsOtpModal');
        const closeBtn = document.getElementById('closeSmsModal');
        const boxes = [...document.querySelectorAll('#otpBoxes input')];
        const resendBtn = document.getElementById('resendSmsOtp');
        const modalMessage = document.getElementById('smsModalMessage');
        const otpPhoneLabel = document.getElementById('otpPhoneLabel');
        const csrf = document.querySelector('#profileForm [name="csrf_token"]').value;
        let verifiedPhone = normalizeLocal(row.dataset.verifiedPhone || '');
        let activePhone = '';
        let resendDeadline = 0;
        let resendTimer = null;
        let sending = false;
        let verifying = false;

        function normalizeLocal(value) {
            const digits = (value || '').replace(/\D/g, '');
            if (/^09\d{9}$/.test(digits)) return digits;
            if (/^639\d{9}$/.test(digits)) return '0' + digits.slice(2);
            if (/^9\d{9}$/.test(digits)) return '0' + digits;
            return '';
        }
        function mask(value) {
            const local = normalizeLocal(value);
            return local ? local.slice(0, 4) + ' *** ' + local.slice(-4) : value;
        }
        function setPhoneState() {
            const current = normalizeLocal(phone.value);
            const ok = !!verifiedPhone && current === verifiedPhone;
            sendBtn.classList.toggle('is-verified', ok);
            sendBtn.querySelector('i').className = 'fa-solid ' + (ok ? 'fa-circle-check' : 'fa-shield-halved');
            sendBtn.querySelector('span').textContent = ok ? 'Verified' : 'Verify';
            check.classList.toggle('is-visible', ok);
            status.className = 'phone-status ' + (ok ? 'success' : '');
            status.textContent = ok ? 'This contact number is verified by SMS.' : 'Verify your mobile number to enable appointment booking.';
        }
        let lockTimer = null;
        function setBoxes(state, disabled) {
            boxes.forEach(b => { b.disabled = !!disabled; b.classList.toggle('is-error', state === 'error'); b.classList.toggle('is-success', state === 'success'); });
        }
        function clearBoxes(focus = true) {
            boxes.forEach(b => { b.value = ''; });
            if (focus) setTimeout(() => boxes[0].focus(), 30);
        }
        function codeValue() { return boxes.map(b => b.value).join(''); }
        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            clearInterval(lockTimer);
            setBoxes('', false);
            clearBoxes();
        }
        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
        function setModalMessage(message, type = '') {
            modalMessage.textContent = message;
            modalMessage.className = 'sms-modal-message ' + type;
        }
        function tickResend() {
            const seconds = Math.ceil((resendDeadline - Date.now()) / 1000);
            if (seconds <= 0) {
                clearInterval(resendTimer);
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend code';
                return;
            }
            resendBtn.disabled = true;
            resendBtn.textContent = 'Resend code in ' + seconds + 's';
        }
        function startResendTimer(seconds) {
            resendDeadline = Date.now() + Math.max(1, seconds) * 1000;
            clearInterval(resendTimer);
            tickResend();
            resendTimer = setInterval(tickResend, 1000);
        }
        async function requestOtp() {
            if (sending) return;
            const current = normalizeLocal(phone.value);
            if (!current) {
                status.className = 'phone-status error';
                status.textContent = 'Enter a valid Philippine mobile number, for example 09171234567.';
                phone.focus();
                return;
            }
            if (verifiedPhone && current === verifiedPhone) {
                status.className = 'phone-status success';
                status.textContent = 'This contact number is already verified.';
                return;
            }

            // Open the OTP dialog immediately after Verify is clicked.
            // This avoids the old behavior where the SMS could arrive but the dialog
            // never appeared because the browser was still waiting for the API response.
            activePhone = current;
            otpPhoneLabel.textContent = mask(current);
            openModal();
            setModalMessage('Sending your 6-digit OTP by SMS...');

            sending = true;
            sendBtn.disabled = true;
            sendBtn.querySelector('span').textContent = 'Sending...';
            resendBtn.disabled = true;
            status.className = 'phone-status';
            status.textContent = 'Sending a verification code by SMS...';

            try {
                const body = new URLSearchParams({ phone: current, csrf_token: csrf });
                const response = await fetch('send_phone_otp.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (parseError) {
                    throw new Error('The server returned an invalid response. Please refresh the page and try again.');
                }

                if (!data.ok) { const err = new Error(data.message || 'Unable to send OTP.'); err.retryAfter = data.retry_after; throw err; }

                otpPhoneLabel.textContent = data.masked_phone || mask(current);
                status.className = 'phone-status';
                status.textContent = data.message || 'OTP sent successfully.';
                setModalMessage(data.delivery === 'log' ? '' : 'OTP sent. Enter the 6-digit code you received.', 'success');
                startResendTimer(Number(data.retry_after) || 30);
                clearInterval(lockTimer);
                setBoxes('', false);
                clearBoxes();
            } catch (error) {
                const message = error.message || 'Unable to send OTP. Please try again.';
                status.className = 'phone-status error';
                status.textContent = message;
                setModalMessage(message, 'error');
                const wait = Number(error.retryAfter) || 0;
                if (wait > 0) startResendTimer(wait); else { resendBtn.disabled = false; resendBtn.textContent = 'Resend code'; }
            } finally {
                sending = false;
                sendBtn.disabled = false;
                setPhoneState();
            }
        }

        // Wrong-code rules (enforced by the server): 1st wrong code = wait 15 seconds, then try again;
        // 2nd wrong code = the code is retired and Resend becomes available after 30 seconds.
        function lockBoxes(seconds, text) {
            const end = Date.now() + seconds * 1000;
            setBoxes('error', true);
            clearInterval(lockTimer);
            const tick = () => {
                const left = Math.ceil((end - Date.now()) / 1000);
                if (left <= 0) { clearInterval(lockTimer); setBoxes('', false); clearBoxes(); setModalMessage('Enter the 6-digit code again.'); status.className = 'phone-status'; status.textContent = 'Enter the 6-digit code again.'; return; }
                const msg = text.replace('{s}', left);
                setModalMessage(msg, 'error'); status.className = 'phone-status error'; status.textContent = msg;
            };
            tick(); lockTimer = setInterval(tick, 1000);
        }
        async function verifyOtp() {
            if (verifying) return;
            const code = codeValue();
            if (!/^\d{6}$/.test(code)) return;
            if (!activePhone || activePhone !== normalizeLocal(phone.value)) {
                setModalMessage('The contact number changed. Close this window and request a new OTP.', 'error');
                return;
            }

            verifying = true;
            setBoxes('', true);
            setModalMessage('Checking your code...');
            try {
                const body = new URLSearchParams({ phone: activePhone, otp: code, csrf_token: csrf });
                const response = await fetch('verify_phone_otp.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body
                });
                const data = await response.json();
                if (data.ok) {
                    verifiedPhone = normalizeLocal(data.phone || activePhone);
                    phone.value = verifiedPhone;
                    setPhoneState();
                    setBoxes('success', true);
                    setModalMessage('Contact number verified successfully.', 'success');
                    status.className = 'phone-status success';
                    status.textContent = 'Contact number verified successfully.';
                    setTimeout(closeModal, 900);
                    return;
                }
                if (data.code === 'wrong' || data.code === 'locked') {
                    lockBoxes(Number(data.retry_after) || 15, 'Wrong OTP. Try again in {s} seconds.');
                } else {
                    // Second wrong code (or an expired code): request a new one.
                    clearInterval(lockTimer);
                    setBoxes('error', true);
                    const wait = Number(data.resend_after) || 0;
                    const msg = data.code === 'retired' ? 'Wrong OTP again. Request a new code' + (wait ? ' in ' + wait + ' seconds.' : '.') : (data.message || 'This code is no longer valid. Please request a new code.');
                    setModalMessage(msg, 'error');
                    status.className = 'phone-status error';
                    status.textContent = msg;
                    if (wait > 0) startResendTimer(wait); else { clearInterval(resendTimer); resendBtn.disabled = false; resendBtn.textContent = 'Resend code'; }
                }
            } catch (error) {
                setBoxes('', false);
                setModalMessage('Unable to verify the code right now. Please try again.', 'error');
            } finally {
                verifying = false;
            }
        }

        // Age appears as soon as a birthday is picked, before the profile is saved.
        const birthday = document.getElementById('birthday');
        const ageDisplay = document.getElementById('ageDisplay');
        const birthdayHint = document.getElementById('birthdayHint');
        function updateAge() {
            const value = birthday.value;
            birthdayHint.textContent = '';
            birthdayHint.className = 'field-hint';
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) { ageDisplay.value = ''; return; }
            const [y, m, d] = value.split('-').map(Number);
            const today = new Date();
            let age = today.getFullYear() - y;
            if (today.getMonth() + 1 < m || (today.getMonth() + 1 === m && today.getDate() < d)) age--;
            if (age < 0 || y < 1900) { ageDisplay.value = ''; birthdayHint.textContent = 'Please enter a valid birth date.'; birthdayHint.className = 'field-hint error'; return; }
            ageDisplay.value = age + (age === 1 ? ' year old' : ' years old');
            if (age < 16) { birthdayHint.textContent = 'Patients must be 16 years or older to book appointments.'; birthdayHint.className = 'field-hint error'; }
        }
        birthday.addEventListener('input', updateAge);
        birthday.addEventListener('change', updateAge);
        updateAge();

        phone.addEventListener('input', () => {
            phone.value = phone.value.replace(/[^0-9+\-()\s]/g, '');
            setPhoneState();
        });
        sendBtn.addEventListener('click', requestOtp);
        resendBtn.addEventListener('click', requestOtp);
        // Six single-digit boxes: typing moves forward, Backspace moves back, pasting fills them all,
        // and the code is verified automatically as soon as all 6 digits are in.
        boxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                const digits = box.value.replace(/\D/g, '');
                if (digits.length > 1) { digits.slice(0, 6 - i).split('').forEach((d, k) => { boxes[i + k].value = d; }); }
                else box.value = digits;
                const next = boxes.find(b => !b.value);
                if (box.value && next) next.focus();
                if (/^\d{6}$/.test(codeValue())) verifyOtp();
            });
            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); e.preventDefault(); }
                if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
                if (e.key === 'ArrowRight' && i < 5) boxes[i + 1].focus();
            });
            box.addEventListener('paste', e => {
                const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                if (!digits) return;
                e.preventDefault();
                boxes.forEach((b, k) => { b.value = digits[k] || ''; });
                (boxes[digits.length] || boxes[5]).focus();
                if (digits.length === 6) verifyOtp();
            });
            box.addEventListener('focus', () => box.select());
        });
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });
        setPhoneState();
    })();
</script>
<?php ui_footer(); ?>