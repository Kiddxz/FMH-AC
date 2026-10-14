/* Shared signup/password-recovery sender. Success requires server-confirmed SMTP acceptance. */
window.SupremeDentOtp = {
    bind(formId, purpose, validate) {
        const form = document.getElementById(formId);
        const button = document.getElementById('sendOtp');
        const help = document.getElementById('otpHelp');
        const email = document.getElementById('email');
        help.setAttribute('role', 'status');
        help.setAttribute('aria-live', 'polite');
        let timer, deadline = 0, sending = false;
        const idleLabel = button.textContent.trim() || 'Send OTP';
        function restore() {
            button.disabled = false;
            button.textContent = idleLabel;
        }
        function tick() {
            const seconds = Math.ceil((deadline - Date.now()) / 1000);
            if (seconds <= 0) { clearInterval(timer); restore(); return; }
            button.disabled = true;
            button.textContent = 'Resend in ' + seconds + 's';
        }
        email.addEventListener('input', () => {
            if (!sending) { help.textContent = ''; help.className = 'otp-help'; }
            document.getElementById('otp').value = '';
        });
        form.addEventListener('submit', event => {
            if (sending) { event.preventDefault(); help.textContent = 'Please wait for the email request to finish.'; }
        });
        button.addEventListener('click', async () => {
            if (sending || Date.now() < deadline) return;
            help.className = 'otp-help';
            if (!validate()) {
                help.textContent = purpose === 'signup' ? 'Enter your first name, last name, and a valid email first.' : 'Enter a valid registered email first.';
                help.classList.add('bad');
                return;
            }
            const requestedEmail = email.value.trim().toLowerCase();
            const body = new URLSearchParams({ email: requestedEmail, purpose,
                csrf_token: form.querySelector('[name="csrf_token"]').value });
            for (const name of ['first_name', 'last_name']) {
                const field = form.querySelector('[name="' + name + '"]');
                if (field) body.set(name, field.value.trim());
            }
            sending = true; button.disabled = true; button.textContent = 'Sending...';
            help.textContent = 'Sending a verification code to ' + requestedEmail + '...';
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 65000);
            try {
                const response = await fetch('send_otp.php', { method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body, signal: controller.signal });
                let data;
                try { data = await response.json(); }
                catch (_) { throw new Error('The server returned an invalid response. Please contact the clinic.'); }
                if (typeof data.ok !== 'boolean' || typeof data.message !== 'string') throw new Error('The server returned an invalid response.');
                const changed = email.value.trim().toLowerCase() !== requestedEmail;
                help.textContent = changed ? 'The email address changed during sending. Request a code for the current address.' : data.message;
                help.classList.add(data.ok && !changed ? 'good' : 'bad');
                const seconds = Math.min(3600, Math.max(0, Number(data.retry_after) || (data.ok ? 60 : 0)));
                if (seconds) { deadline = Date.now() + seconds * 1000; tick(); timer = setInterval(tick, 1000); }
                else restore();
                if (data.ok && !changed) document.getElementById('otp').focus();
            } catch (error) {
                help.textContent = error.name === 'AbortError' ? 'The email request timed out. Wait 60 seconds, check your inbox, then retry.' : (error.message || 'Could not request a code. Check your connection and try again.');
                help.classList.add('bad');
                restore();
            } finally { clearTimeout(timeout); sending = false; }
        });
    }
};
