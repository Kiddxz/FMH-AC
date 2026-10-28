<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Verify Email</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <style>
    /* 6 boxes for the 6-digit code. Without JavaScript the normal text box is shown instead. */
    .code-boxes { display: none; justify-content: center; gap: 10px; margin-top: 4px; }
    .code-ready .code-boxes { display: flex; }
    .code-ready .code-input { position: absolute; width: 1px; height: 1px; padding: 0; border: 0; opacity: 0; pointer-events: none; }
    .form-group input.code-box { flex: 1; width: auto; min-width: 0; max-width: 58px; height: 60px; padding: 0; border: 1.5px solid #ddd5ca; border-radius: 12px; background: #fff; color: #26364a; font-family: inherit; font-size: 26px; font-weight: 700; text-align: center; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s; }
    .form-group input.code-box:focus { outline: none; border-color: #e89427; box-shadow: 0 0 0 3px rgba(232, 148, 39, 0.15); }
    .form-group input.code-box.filled { border-color: #e89427; background: #fffaf3; }
    .code-error .form-group input.code-box { border-color: #d9534f; }
    @media (max-width: 420px) { .code-boxes { gap: 6px; } .form-group input.code-box { height: 52px; font-size: 22px; } }
  </style>
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card">
      <div class="forgot-icon" style="color: #e89427;">
        <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
      </div>
      <h1>Verify Your Email</h1>
      <p class="account-description">Enter the 6-digit code we sent to <strong>{{ $email }}</strong>. The code expires in 10 minutes.</p>
      {{-- green only after "Send a new code"; red when the code is wrong --}}
      @include('partials.alerts')
      <form action="{{ route('register.verify.check') }}" method="post" id="verifyForm" class="{{ $errors->has('code') ? 'code-error' : '' }}">
        @csrf
        <div class="form-group" style="position: relative;">
          <label for="code" id="codeLabel">Verification Code</label>
          <input type="text" id="code" name="code" class="code-input" placeholder="Enter the 6-digit code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
          <div class="code-boxes" role="group" aria-labelledby="codeLabel">
            @for ($i = 1; $i <= 6; $i++)
              <input type="text" class="code-box" inputmode="numeric" maxlength="1" autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }} of 6">
            @endfor
          </div>
        </div>
        <button class="resetbtn" type="submit">Verify Email</button>
      </form>
      <div class="divider">
        <span>OR</span>
      </div>
      <div class="relog">
        <form action="{{ route('register.resend') }}" method="post">
          @csrf
          <p>
            Didn't get the code?
            <button type="submit" style="border: none; background: none; padding: 0; color: #e89427; font-weight: bold; font-size: inherit; font-family: inherit; cursor: pointer;">Send a new code</button>
          </p>
        </form>
        <p>
          Wrong email?
          <a href="{{ route('register') }}">Register again</a>
        </p>
      </div>
    </div>
  </main>
  <script>
    (function () {
      // The 6 boxes fill the real "code" field. Typing moves to the next box, Backspace goes back,
      // and pasting the whole code (e.g. 248086) fills all 6 boxes at once.
      var form = document.getElementById('verifyForm');
      var real = document.getElementById('code');
      var boxes = Array.prototype.slice.call(form.querySelectorAll('.code-box'));
      form.classList.add('code-ready');
      real.removeAttribute('autofocus');
      // the boxes check the code below (and the server checks it again), so the hidden field needs no browser check
      real.removeAttribute('required');
      real.removeAttribute('pattern');

      function sync() {
        real.value = boxes.map(function (b) { return b.value; }).join('');
        boxes.forEach(function (b) { b.classList.toggle('filled', b.value !== ''); });
        form.classList.remove('code-error');
      }
      function fill(from, digits) {
        for (var i = 0; i < digits.length && from + i < boxes.length; i++) boxes[from + i].value = digits[i];
        sync();
        boxes[Math.min(from + digits.length, boxes.length - 1)].focus();
      }

      boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
          var digits = box.value.replace(/\D/g, '');
          box.value = '';
          if (digits) fill(index, digits.split(''));   // also handles the phone filling in the whole code
          else sync();
        });
        box.addEventListener('keydown', function (event) {
          if (event.key === 'Backspace' && box.value === '' && index > 0) {
            boxes[index - 1].value = '';
            boxes[index - 1].focus();
            sync();
            event.preventDefault();
          } else if (event.key === 'ArrowLeft' && index > 0) {
            boxes[index - 1].focus();
          } else if (event.key === 'ArrowRight' && index < boxes.length - 1) {
            boxes[index + 1].focus();
          }
        });
        box.addEventListener('paste', function (event) {
          var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
          if (digits) {
            event.preventDefault();
            fill(index, digits.split(''));
          }
        });
        box.addEventListener('focus', function () { box.select(); });
      });

      form.addEventListener('submit', function (event) {
        sync();
        if (real.value.length !== 6) {
          event.preventDefault();
          form.classList.add('code-error');
          var empty = boxes.filter(function (b) { return b.value === ''; })[0];
          (empty || boxes[0]).focus();
        }
      });

      boxes[0].focus();
    })();
  </script>
</body>
</html>
