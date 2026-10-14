<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/otp_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$message = '';
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$agreed = (($_POST['agree'] ?? '') === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $agree = $_POST['agree'] ?? '';
    $namePattern = "/^[\\p{L}\\s.'-]{2,100}$/u";

    if (!csrf_valid($_POST['csrf_token'] ?? null))
        $message = 'Your session expired. Refresh the page and try again.';
    elseif (!preg_match($namePattern, $firstName) || !preg_match($namePattern, $lastName))
        $message = 'Enter a valid first and last name.';
    elseif (!valid_email_address($email))
        $message = 'Enter a valid email address.';
    elseif (!valid_strong_password($password))
        $message = 'Password must be 8-128 characters and include uppercase, lowercase, a number, and a special character.';
    elseif ($password !== $confirm)
        $message = 'Passwords do not match.';
    elseif ($agree !== '1')
        $message = 'Please read and agree to the Terms and Conditions and Privacy Notice.';
    elseif (!preg_match('/^\d{6}$/', $otp) || !otp_consume($conn, $email, 'signup', $otp))
        $message = 'The OTP is invalid, expired, or already used. Request a new code.';
    else {
        $check = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
        if ($exists)
            $message = 'This email is already registered. Please log in instead.';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'patient';
            $status = 'active';
            $stmt = $conn->prepare('INSERT INTO users(first_name,last_name,email,password,role,status) VALUES(?,?,?,?,?,?)');
            $stmt->bind_param('ssssss', $firstName, $lastName, $email, $hash, $role, $status);
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                $stmt->close();
                $p = $conn->prepare('INSERT IGNORE INTO patient_profiles(user_id) VALUES(?)');
                $p->bind_param('i', $newId);
                $p->execute();
                $p->close();
                log_activity($conn, $newId, 'Patient account created', 'Authentication', 'Verified by email OTP');
                header('Location: login.php?registered=1');
                exit;
            }
            $stmt->close();
            $message = 'Unable to create your account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="description" content="Create a secure SupremeDent patient account.">
    <title>Create Patient Account | SupremeDent</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="auth-page auth-stacked">
        <div class="auth-shell auth-shell-signup">
            <header class="auth-welcome">
                <div class="auth-welcome-content">
                    <div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent logo"></div>
                    <h1>Welcome to SupremeDent</h1>
                    <p>Create your patient account to request appointments and view your dental records.</p>
                </div>
            </header>
            <main class="auth-form-panel"><a href="../index.php" class="auth-back"><i
                        class="fa-solid fa-arrow-left"></i> Back to Home</a>
                <div class="auth-form-wrap">
                    <div class="auth-wordmark">SUPREMEDENT</div>
                    <p class="auth-kicker">Patient registration</p>
                    <?php if ($message): ?>
                        <div class="auth-message is-error" style="display:block" role="alert"><i
                                class="fa-solid fa-circle-exclamation"></i> <?= e($message) ?></div><?php endif; ?>
                    <form method="post" autocomplete="off" id="signupForm" novalidate><input type="hidden" name="csrf_token"
                            value="<?= e(csrf_token()) ?>">
                        <div class="credential-decoy" aria-hidden="true"><input type="text" tabindex="-1"
                                autocomplete="username"><input type="password" tabindex="-1"
                                autocomplete="new-password"></div>
                        <div class="auth-grid two-up">
                            <div class="auth-field"><label for="first_name">First Name</label><input type="text"
                                    id="first_name" name="first_name" class="input-field" maxlength="100"
                                    value="<?= e($firstName) ?>" placeholder="First name" autocomplete="off" required>
                                <div class="field-feedback" id="firstNameFeedback" aria-live="polite"></div>
                            </div>
                            <div class="auth-field"><label for="last_name">Last Name</label><input type="text"
                                    id="last_name" name="last_name" class="input-field" maxlength="100"
                                    value="<?= e($lastName) ?>" placeholder="Last name" autocomplete="off" required>
                                <div class="field-feedback" id="lastNameFeedback" aria-live="polite"></div>
                            </div>
                        </div>
                        <div class="auth-field"><label for="email">Email Address</label><input type="email" id="email"
                                name="email" class="input-field" maxlength="150" value="<?= e($email) ?>"
                                placeholder="you@example.com" autocomplete="off" autocapitalize="none"
                                spellcheck="false" data-lpignore="true" data-1p-ignore="true" required>
                            <div class="field-feedback" id="emailFeedback" aria-live="polite"></div>
                        </div>
                        <div class="auth-field"><label for="otp">Email OTP</label>
                            <div class="otp-inline"><input type="text" inputmode="numeric" pattern="[0-9]{6}"
                                    maxlength="6" id="otp" name="otp" class="input-field" placeholder="6-digit code"
                                    autocomplete="one-time-code" required><button type="button" class="otp-send"
                                    id="sendOtp"> Send OTP</button></div>
                            <div class="field-feedback" id="otpFeedback" aria-live="polite"></div>
                            <div class="otp-help" id="otpHelp">Send a code to the email address entered above.</div>
                        </div>
                        <div class="auth-field"><label for="password">Password <small>8+ characters</small></label>
                            <div class="password-control"><input type="password" id="password" name="password"
                                    class="input-field" maxlength="128" placeholder="Create a password"
                                    autocomplete="new-password" data-lpignore="true" data-1p-ignore="true"
                                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,128}" required><button
                                    type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><i
                                        class="fa-regular fa-eye-slash"></i></button></div>
                            <div class="password-strength" id="passwordStrength" data-level=""><div class="password-meter" aria-hidden="true"><span id="passwordMeter"></span></div><span class="password-strength-label" id="passwordStrengthLabel" aria-live="polite"></span></div>
                            <div class="validation-list" id="validationList"><span data-rule="length">8+
                                    characters</span><span data-rule="upper">Uppercase letter</span><span
                                    data-rule="lower">Lowercase letter</span><span data-rule="number">Number</span><span
                                    data-rule="special">Special character (!@#$...)</span></div>
                        </div>
                        <div class="auth-field"><label for="confirm_password">Confirm Password</label>
                            <div class="password-control"><input type="password" id="confirm_password"
                                    name="confirm_password" class="input-field" maxlength="128"
                                    placeholder="Re-enter your password" autocomplete="new-password"
                                    data-lpignore="true" data-1p-ignore="true" required><button type="button"
                                    class="password-toggle" data-toggle-password="confirm_password" aria-label="Show password" aria-pressed="false"><i
                                        class="fa-regular fa-eye-slash"></i></button></div>
                            <div class="field-feedback" id="passwordMatchFeedback" aria-live="polite"></div>
                        </div>
                        <label class="terms-check"><input type="checkbox" id="agreeTerms" name="agree" value="1" <?= $agreed ? 'checked' : '' ?> required><span>I confirm that the information I provide is accurate and I have read and agree to the <button type="button" class="terms-link" id="openTerms">Terms and Conditions</button> and <button type="button" class="terms-link" id="openPrivacy">Privacy Notice</button>.</span></label>
                        <div class="field-feedback" id="agreeFeedback" aria-live="polite"></div>
                        <button type="submit" class="btn btn-primary"> Sign up </button>
                    </form>
                    <div class="auth-footer">Already have an account? <a href="login.php">Log in</a></div>
                </div>
            </main>
        </div>
    </div>
    <div class="signup-terms-modal" id="signupTermsModal" aria-hidden="true">
        <div class="signup-terms-dialog" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
            <button type="button" class="terms-modal-close" id="closeTermsModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            <div class="terms-modal-head">
                <span class="terms-modal-icon"><i class="fa-solid fa-file-shield"></i></span>
                <div><p>SUPREMEDENT PATIENT PORTAL</p><h2 id="termsModalTitle">Terms and Conditions</h2></div>
            </div>
            <div class="terms-modal-body" id="termsModalBody">
                <section data-section="terms">
                    <h3>1. Patient Account</h3>
                    <p>Your account is for your personal use. You are responsible for providing accurate information, keeping your password private, and promptly updating incorrect or outdated profile details.</p>
                    <h3>2. Appointments</h3>
                    <p>Appointment requests are subject to clinic confirmation and availability. Submitting a request does not guarantee an appointment until it is approved by the clinic.</p>
                    <h3>3. Dental Information</h3>
                    <p>Information shown in the portal, including dental records, treatment plans, and 3D tooth concerns, supports clinic communication and does not replace an in-person dental examination or professional advice.</p>
                    <h3>4. Responsible Use</h3>
                    <p>You must not misuse the website, attempt to access another user's account, submit false information, interfere with the system, or use the portal for unlawful purposes.</p>
                    <h3>5. OTP and Account Security</h3>
                    <p>Verification codes are confidential. Never share your OTP or password. SupremeDent staff should not ask you to disclose your password or a current OTP.</p>
                    <h3>6. Service Availability</h3>
                    <p>The clinic may temporarily limit access for maintenance, security, or technical issues. Features may be updated as the patient portal improves.</p>
                </section>
                <section data-section="privacy">
                    <h3>Privacy Notice</h3>
                    <p>SupremeDent collects the information needed to create and manage your patient account, process appointment requests, maintain dental records, communicate with you, and protect the portal from misuse.</p>
                    <p>Your profile may include your name, email address, verified contact number, birthday, address, gender, appointments, treatment information, and other clinic records that you or authorized clinic staff provide.</p>
                    <p>Access is limited according to account roles. Patients should only access their own portal information, while authorized clinic staff access information needed for clinic operations and patient care.</p>
                    <p>By creating an account, you acknowledge that the information you submit will be stored and processed for SupremeDent patient-portal and clinic-service purposes.</p>
                </section>
            </div>
            <div class="terms-modal-actions"><button type="button" class="btn btn-secondary" id="termsCloseAction">Close</button><button type="button" class="btn btn-primary" id="termsAgreeAction"><i class="fa-solid fa-check"></i> I Agree</button></div>
        </div>
    </div>
    <style>
        .terms-link{padding:0;border:0;background:none;color:#0d747a;font:inherit;font-weight:700;text-decoration:underline;cursor:pointer}.signup-terms-modal{display:none;position:fixed;inset:0;z-index:4000;background:rgba(10,35,44,.66);backdrop-filter:blur(5px);padding:20px;align-items:center;justify-content:center}.signup-terms-modal.is-open{display:flex}.signup-terms-dialog{position:relative;width:min(700px,100%);max-height:min(760px,92vh);display:flex;flex-direction:column;background:#fff;border-radius:22px;box-shadow:0 30px 90px rgba(0,0,0,.32);overflow:hidden}.terms-modal-close{position:absolute;top:16px;right:16px;width:38px;height:38px;border:0;border-radius:50%;background:#eef5f4;color:#4c6268;cursor:pointer;z-index:2}.terms-modal-head{display:flex;gap:14px;align-items:center;padding:24px 58px 18px 24px;background:linear-gradient(135deg,#eaf7f5,#eff8fa);border-bottom:1px solid #dce9e8}.terms-modal-head p{margin:0 0 3px;color:#0d747a;font-size:10px;font-weight:800;letter-spacing:.12em}.terms-modal-head h2{margin:0;font:700 25px 'DM Sans',sans-serif;color:#193b43}.terms-modal-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:14px;background:#fff;color:#0d747a;font-size:21px}.terms-modal-body{padding:22px 25px;overflow:auto;color:#536d73;line-height:1.68}.terms-modal-body h3{margin:17px 0 6px;color:#22454c;font-size:14px}.terms-modal-body h3:first-child{margin-top:0}.terms-modal-body p{margin:0 0 12px}.terms-modal-body section+section{margin-top:24px;padding-top:20px;border-top:1px solid #dfe9e8}.terms-modal-actions{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid #dce9e8;background:#fff}@media(max-width:620px){.signup-terms-dialog{max-height:94vh}.terms-modal-head{padding:20px 52px 16px 18px}.terms-modal-body{padding:18px}.terms-modal-actions{padding:14px 18px}.terms-modal-actions .btn{flex:1}}
    </style>
    <script src="../assets/js/auth_otp.js?v=20261009"></script>
    <script>
        function validSupremeDentEmail(value) { value = value.trim(); if (value.length < 5 || value.length > 150 || value.split('@').length !== 2) return false; const [local, domainRaw] = value.split('@'), domain = domainRaw.toLowerCase(); if (!/^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+$/.test(local) || local.includes('..')) return false; if (!/^(?=.{3,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i.test(domain) || domain.includes('..')) return false; if (/\.(com|net|org|edu|gov|ph)\.\1$/i.test(domain)) return false; const providers = { 'gmail.': ['gmail.com'], 'googlemail.': ['googlemail.com'], 'outlook.': ['outlook.com'], 'hotmail.': ['hotmail.com'], 'icloud.': ['icloud.com'] }; for (const [prefix, allowed] of Object.entries(providers)) { if (domain.startsWith(prefix) && !allowed.includes(domain)) return false } return true }
        // Every field is checked while the patient types, so mistakes show up before pressing Sign up.
        const $id = id => document.getElementById(id);
        const signupForm = $id('signupForm'), emailInput = $id('email'), emailFeedback = $id('emailFeedback');
        const touched = new Set();
        function setFieldState(input, feedback, message, ok) {
            const show = touched.has(input.id) || input.value !== '';
            input.classList.toggle('is-invalid', show && !ok);
            input.classList.toggle('is-valid', show && ok && input.value !== '');
            feedback.textContent = show && !ok ? message : '';
            feedback.className = 'field-feedback' + (show && !ok ? ' bad' : '');
            input.setCustomValidity(ok ? '' : message);
            return ok;
        }
        const namePattern = /^[\p{L}\s.'-]{2,100}$/u;
        function checkName(id, label) {
            const input = $id(id), v = input.value.trim();
            const msg = !v ? label + ' is required.' : (v.length < 2 ? label + ' must be at least 2 characters.' : (!namePattern.test(v) ? label + ' can only contain letters, spaces, periods, apostrophes, and hyphens.' : ''));
            return setFieldState(input, $id(id === 'first_name' ? 'firstNameFeedback' : 'lastNameFeedback'), msg, !msg);
        }
        let emailTaken = null, emailCheckTimer = null, lastCheckedEmail = '';
        function updateEmailFeedback() {
            const v = emailInput.value.trim();
            let msg = '';
            if (!v) msg = 'Email address is required.';
            else if (!validSupremeDentEmail(v)) msg = 'Please enter a valid email address (for example, name@gmail.com).';
            else if (emailTaken === true && lastCheckedEmail === v.toLowerCase()) msg = 'This email is already registered. Please log in instead.';
            return setFieldState(emailInput, emailFeedback, msg, !msg);
        }
        function scheduleEmailCheck() {
            clearTimeout(emailCheckTimer);
            const v = emailInput.value.trim().toLowerCase();
            if (!validSupremeDentEmail(v)) return;
            emailCheckTimer = setTimeout(async () => {
                try {
                    const res = await fetch('check_email.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ email: v, csrf_token: signupForm.querySelector('[name="csrf_token"]').value }) });
                    const data = await res.json();
                    if (data.ok && emailInput.value.trim().toLowerCase() === v) { lastCheckedEmail = v; emailTaken = data.valid && !data.available; updateEmailFeedback(); }
                } catch (_) { /* The server still validates on submit. */ }
            }, 450);
        }
        emailInput.addEventListener('input', () => { emailTaken = null; updateEmailFeedback(); scheduleEmailCheck(); });
        // Open eye = password is visible, slashed eye = password is hidden.
        document.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', function () { const input = $id(this.dataset.togglePassword), icon = this.querySelector('i'); const show = input.type === 'password'; input.type = show ? 'text' : 'password'; icon.classList.toggle('fa-eye', show); icon.classList.toggle('fa-eye-slash', !show); this.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); this.setAttribute('aria-pressed', String(show)); }));
        const pwd = $id('password'), confirmPwd = $id('confirm_password'), meter = $id('passwordMeter'), strength = $id('passwordStrength'), strengthLabel = $id('passwordStrengthLabel'), matchFeedback = $id('passwordMatchFeedback');
        function updateRules() {
            const v = pwd.value, rules = { length: v.length >= 8, upper: /[A-Z]/.test(v), lower: /[a-z]/.test(v), number: /\d/.test(v), special: /[^A-Za-z0-9]/.test(v) };
            let score = 0; Object.entries(rules).forEach(([k, ok]) => { document.querySelector(`[data-rule="${k}"]`).classList.toggle('ok', ok); if (ok) score++ });
            const allOk = Object.values(rules).every(Boolean);
            // Red until most rules pass, yellow when nearly there, green only when every rule passes.
            const level = !v ? '' : (allOk ? 'strong' : (score >= 3 ? 'medium' : 'weak'));
            strength.dataset.level = level;
            meter.style.width = !v ? '0' : (allOk ? '100%' : Math.max(20, score * 20) + '%');
            strengthLabel.textContent = { weak: 'Weak', medium: 'Fair', strong: 'Strong' }[level] || '';
            pwd.classList.toggle('is-invalid', !!v && !allOk && touched.has('password'));
            pwd.classList.toggle('is-valid', allOk);
            pwd.setCustomValidity(allOk ? '' : 'Use uppercase, lowercase, number, special character, and at least 8 characters.');
            return allOk;
        }
        function updateMatch() { if (!confirmPwd.value) { matchFeedback.textContent = touched.has('confirm_password') ? 'Please re-enter your password.' : ''; matchFeedback.className = 'field-feedback' + (touched.has('confirm_password') ? ' bad' : ''); confirmPwd.setCustomValidity('Please re-enter your password.'); confirmPwd.classList.remove('is-valid'); return false } const ok = pwd.value === confirmPwd.value; matchFeedback.textContent = ok ? 'Passwords match.' : 'Passwords do not match.'; matchFeedback.className = 'field-feedback ' + (ok ? 'good' : 'bad'); confirmPwd.setCustomValidity(ok ? '' : 'Passwords do not match.'); confirmPwd.classList.toggle('is-invalid', !ok); confirmPwd.classList.toggle('is-valid', ok); return ok }
        const otpInput = $id('otp'), otpFeedback = $id('otpFeedback');
        function checkOtp() { const v = otpInput.value; const msg = !v ? 'Enter the 6-digit code sent to your email.' : (!/^\d{6}$/.test(v) ? 'The code must be exactly 6 digits.' : ''); return setFieldState(otpInput, otpFeedback, msg, !msg); }
        const agree = $id('agreeTerms'), agreeFeedback = $id('agreeFeedback');
        function checkAgree() { const ok = agree.checked; agreeFeedback.textContent = !ok && touched.has('agreeTerms') ? 'Please agree to the Terms and Conditions and Privacy Notice.' : ''; agreeFeedback.className = 'field-feedback' + (agreeFeedback.textContent ? ' bad' : ''); return ok; }
        [['first_name', () => checkName('first_name', 'First name')], ['last_name', () => checkName('last_name', 'Last name')], ['email', updateEmailFeedback], ['otp', checkOtp], ['password', () => { updateRules(); if (confirmPwd.value) updateMatch(); }], ['confirm_password', updateMatch]].forEach(([id, fn]) => {
            const input = $id(id);
            input.addEventListener('blur', () => { touched.add(id); fn(); });
            input.addEventListener('input', fn);
        });
        otpInput.addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6); checkOtp(); });
        agree.addEventListener('change', () => { touched.add('agreeTerms'); checkAgree(); });
        updateRules();
        if (emailInput.value) { updateEmailFeedback(); scheduleEmailCheck(); }
        SupremeDentOtp.bind('signupForm', 'signup', () => { touched.add('first_name'); touched.add('last_name'); touched.add('email'); const a = checkName('first_name', 'First name'), b = checkName('last_name', 'Last name'), c = updateEmailFeedback(); return a && b && c; });
        signupForm.addEventListener('submit', e => {
            ['first_name', 'last_name', 'email', 'otp', 'password', 'confirm_password', 'agreeTerms'].forEach(id => touched.add(id));
            const results = [checkName('first_name', 'First name'), checkName('last_name', 'Last name'), updateEmailFeedback(), checkOtp(), updateRules(), updateMatch(), checkAgree()];
            if (results.includes(false)) { e.preventDefault(); const firstBad = signupForm.querySelector('.is-invalid') || (!agree.checked ? agree : null); if (firstBad) firstBad.focus(); }
        });
        const termsModal = document.getElementById('signupTermsModal'), agreeTerms = document.getElementById('agreeTerms');
        function openTerms(section) { termsModal.classList.add('is-open'); termsModal.setAttribute('aria-hidden','false'); const target = termsModal.querySelector('[data-section="' + section + '"]'); if (target) setTimeout(() => target.scrollIntoView({block:'start'}), 20); }
        function closeTerms() { termsModal.classList.remove('is-open'); termsModal.setAttribute('aria-hidden','true'); }
        document.getElementById('openTerms').addEventListener('click', () => openTerms('terms'));
        document.getElementById('openPrivacy').addEventListener('click', () => openTerms('privacy'));
        document.getElementById('closeTermsModal').addEventListener('click', closeTerms);
        document.getElementById('termsCloseAction').addEventListener('click', closeTerms);
        document.getElementById('termsAgreeAction').addEventListener('click', () => { agreeTerms.checked = true; agreeTerms.dispatchEvent(new Event('change')); closeTerms(); });
        termsModal.addEventListener('click', e => { if (e.target === termsModal) closeTerms(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && termsModal.classList.contains('is-open')) closeTerms(); });
    </script>
</body>

</html>