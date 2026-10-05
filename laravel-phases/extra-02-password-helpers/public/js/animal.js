/*
  FMH Animal Clinic - animal.js (Phase 18 version)

  This file only adds small page behaviors (popup, phone menu, password helpers).
  Laravel (PHP) does the real work: saving data, login, permissions.
  Phase 18: the old sample-page code (edit appointment, backup and settings
  buttons) was removed, because those pages now use real Laravel forms.
*/

document.addEventListener("DOMContentLoaded", function () {

  /* =========================================================
     1. HOME PAGE: "Log in" popup with account type cards
     ========================================================= */
  const loginLink = document.getElementById("loginLink");
  const loginModal = document.getElementById("loginModal");
  const closeLoginModal = document.getElementById("closeLoginModal");
  const accountTypeCards = document.querySelectorAll(".account-type-card");
  const accountContinueBtn = document.getElementById("accountContinueBtn");
  let selectedAccountType = "";

  if (loginLink && loginModal) {
    loginLink.addEventListener("click", function (event) {
      event.preventDefault();
      loginModal.style.display = "flex";
    });
  }

  if (closeLoginModal && loginModal) {
    closeLoginModal.addEventListener("click", function () {
      loginModal.style.display = "none";
    });
  }

  if (loginModal) {
    // Clicking the dark area outside the card closes the popup
    loginModal.addEventListener("click", function (event) {
      if (event.target === loginModal) {
        loginModal.style.display = "none";
      }
    });
  }

  accountTypeCards.forEach(function (card) {
    card.addEventListener("click", function () {
      accountTypeCards.forEach(function (item) {
        item.classList.remove("selected");
      });
      card.classList.add("selected");
      selectedAccountType = card.dataset.role;
      if (accountContinueBtn) {
        accountContinueBtn.disabled = false;
      }
    });
  });

  if (accountContinueBtn) {
    // Every account type now uses ONE login page (decision P15)
    accountContinueBtn.addEventListener("click", function () {
      const roles = {
        owner: "owner",
        assistant: "staff",
        admin: "admin",
        superadmin: "superadmin"
      };
      if (roles[selectedAccountType]) {
        window.location.href = "/login?role=" + roles[selectedAccountType];
      }
    });
  }

  /* =========================================================
     2. PANEL MENU ON A PHONE (Phase 18)
        The "Menu" button opens and closes the menu of the
        Staff, Admin and Super Admin pages.
     ========================================================= */
  const navToggle = document.querySelector(".nav-toggle");
  const panelMenu = document.getElementById("panelMenu");

  if (navToggle && panelMenu) {
    navToggle.addEventListener("click", function () {
      const isOpen = panelMenu.classList.toggle("open");
      navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      navToggle.textContent = isOpen ? "✕ Close" : "☰ Menu";
    });
  }

  /* =========================================================
     3. PASSWORD HELPERS (every page with a password box)
        - an eye button to show or hide what was typed
        - for a NEW password: the rules, a checklist and a
          Weak / Fair / Good / Strong meter
        - for "Confirm password": shows if both passwords match
        The server still checks the real rules (Laravel), this
        only helps the user while typing.
     ========================================================= */
  const passwordBoxes = document.querySelectorAll('input[type="password"]');

  if (passwordBoxes.length) {
    const style = document.createElement("style");
    style.textContent =
      ".pw-wrap { position: relative; display: block; }" +
      ".pw-wrap input { padding-right: 46px !important; }" +
      ".pw-eye { position: absolute; top: 50%; right: 8px; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; padding: 0; border: none; border-radius: 8px; background: transparent; color: #64748b; cursor: pointer; }" +
      ".pw-eye:hover { background: #fff1df; color: #e89427; }" +
      ".pw-eye svg { width: 20px; height: 20px; }" +
      ".pw-help { margin-top: 8px; font-size: 13px; }" +
      ".pw-meter { height: 6px; border-radius: 999px; background: #eee6db; overflow: hidden; }" +
      ".pw-meter span { display: block; height: 100%; width: 0; border-radius: 999px; transition: width 0.2s, background 0.2s; }" +
      ".pw-strength { margin: 5px 0 6px; font-weight: bold; }" +
      ".pw-rules { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 3px 12px; color: #94a3b8; }" +
      ".pw-rules li.ok { color: #287a43; }" +
      ".pw-rules small { color: #94a3b8; font-weight: normal; }" +
      ".pw-match { margin-top: 6px; font-size: 13px; font-weight: bold; }" +
      "@media (max-width: 500px) { .pw-rules { grid-template-columns: 1fr; } }";
    document.head.appendChild(style);
  }

  // Line icons for the eye button: slashed eye = password hidden, open eye = password shown
  const iconSvg = function (paths) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + "</svg>";
  };
  const eyeOpenIcon = iconSvg('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>');
  const eyeSlashIcon = iconSvg('<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>');

  // The rules. "required" ones are the same rules the server checks.
  const passwordRules = [
    { label: "At least 8 characters", test: (v) => v.length >= 8, required: true },
    { label: "Has a letter (a–z)", test: (v) => /[A-Za-z]/.test(v), required: true },
    { label: "Has a number (0–9)", test: (v) => /\d/.test(v), required: true },
    { label: "Has a capital letter (A–Z)", test: (v) => /[A-Z]/.test(v), required: false },
    { label: "Has a symbol (! @ # $ …)", test: (v) => /[^A-Za-z0-9]/.test(v), required: false },
    { label: "12 characters or more", test: (v) => v.length >= 12, required: false }
  ];

  const strengthLevels = [
    { text: "Too weak", color: "#d9534f", width: "20%" },
    { text: "Weak", color: "#e8742a", width: "40%" },
    { text: "Fair", color: "#e8a727", width: "60%" },
    { text: "Good", color: "#5aa65a", width: "80%" },
    { text: "Strong", color: "#287a43", width: "100%" }
  ];

  function strengthOf(value) {
    const requiredOk = passwordRules.filter((r) => r.required).every((r) => r.test(value));
    if (!requiredOk) {
      return strengthLevels[0];
    }
    const extras = passwordRules.filter((r) => !r.required && r.test(value)).length;
    return strengthLevels[extras + 1];   // 0 extra = Weak ... 3 extras = Strong
  }

  passwordBoxes.forEach(function (input) {
    // 1. Eye button
    const wrap = document.createElement("span");
    wrap.className = "pw-wrap";
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    const eye = document.createElement("button");
    eye.type = "button";
    eye.className = "pw-eye";
    eye.innerHTML = eyeSlashIcon;
    eye.setAttribute("aria-label", "Show password");
    eye.addEventListener("click", function () {
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      eye.innerHTML = show ? eyeOpenIcon : eyeSlashIcon;
      eye.setAttribute("aria-label", show ? "Hide password" : "Show password");
      input.focus();
    });
    wrap.appendChild(eye);

    // 2. New password: rules, checklist and strength meter
    if (input.name === "password" && input.autocomplete === "new-password") {
      const help = document.createElement("div");
      help.className = "pw-help";
      help.innerHTML =
        '<div class="pw-meter"><span></span></div>' +
        '<div class="pw-strength"></div>' +
        '<ul class="pw-rules">' +
        passwordRules.map((r) => "<li>○ " + r.label + (r.required ? "" : " <small>(stronger)</small>") + "</li>").join("") +
        "</ul>";
      wrap.insertAdjacentElement("afterend", help);

      const bar = help.querySelector(".pw-meter span");
      const label = help.querySelector(".pw-strength");
      const items = help.querySelectorAll(".pw-rules li");

      const update = function () {
        const value = input.value;
        passwordRules.forEach(function (rule, i) {
          const ok = rule.test(value);
          items[i].classList.toggle("ok", ok);
          items[i].firstChild.textContent = (ok ? "✓ " : "○ ") + rule.label + " ";
        });
        if (value === "") {
          bar.style.width = "0";
          label.textContent = "Password strength: —";
          label.style.color = "#94a3b8";
          return;
        }
        const level = strengthOf(value);
        bar.style.width = level.width;
        bar.style.background = level.color;
        label.textContent = "Password strength: " + level.text;
        label.style.color = level.color;
      };
      input.addEventListener("input", update);
      update();
    }

    // 3. Confirm password: do both match?
    if (input.name === "password_confirmation" && input.form) {
      const main = input.form.querySelector('input[name="password"]');
      if (main) {
        const note = document.createElement("div");
        note.className = "pw-match";
        wrap.insertAdjacentElement("afterend", note);

        const check = function () {
          if (input.value === "") {
            note.textContent = "";
          } else if (input.value === main.value) {
            note.textContent = "✓ Passwords match";
            note.style.color = "#287a43";
          } else {
            note.textContent = "✗ Passwords do not match";
            note.style.color = "#d9534f";
          }
        };
        input.addEventListener("input", check);
        main.addEventListener("input", check);
      }
    }
  });

});
