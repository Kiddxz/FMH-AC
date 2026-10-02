/*
  FMH Animal Clinic - animal.js (Phase 3 version)

  This file only adds small page behaviors (popup, buttons).
  Laravel (PHP) does the real work: saving data, login, permissions.
  Phase 3: the temporary demo login (with passwords written here) was removed.
  Logging in is now checked by Laravel against the hashed passwords in MySQL.
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
     2. ADMIN: Appointment details / Edit appointment buttons
     ========================================================= */
  const editAppointmentBtn = document.getElementById("editAppointmentBtn");
  if (editAppointmentBtn) {
    editAppointmentBtn.addEventListener("click", function () {
      window.location.href = "/admin/appointments/1/edit";
    });
  }

  const cancelEditBtn = document.getElementById("cancelEditBtn");
  if (cancelEditBtn) {
    cancelEditBtn.addEventListener("click", function () {
      window.location.href = "/admin/appointments/1";
    });
  }

  const editAppointmentForm = document.getElementById("editAppointmentForm");
  if (editAppointmentForm) {
    // Phase 1: only checks the form. Saving to MySQL comes in Phase 9.
    editAppointmentForm.addEventListener("submit", function (event) {
      event.preventDefault();

      const requiredIds = [
        "editOwnerName", "editPetName", "editServiceName", "editStatus",
        "editAppointmentDate", "editAppointmentTime", "editReason"
      ];

      const missing = requiredIds.some(function (id) {
        const field = document.getElementById(id);
        return !field || field.value.trim() === "";
      });

      if (missing) {
        alert("Please complete all required information.");
        return;
      }

      if (!confirm("Are you sure you want to save these changes?")) {
        return;
      }

      alert("Appointment updated successfully.");
      window.location.href = "/admin";
    });
  }

  /* =========================================================
     3. SUPER ADMIN: backup, settings
        (Backup and settings become real in Phase 17.)
     ========================================================= */
  const backupBtn = document.getElementById("backupBtn");
  const recoveryBtn = document.getElementById("recoveryBtn");
  const backupMessage = document.getElementById("backupMessage");

  if (backupBtn && backupMessage) {
    backupBtn.addEventListener("click", function () {
      backupMessage.textContent = "Real database backup will be added in Phase 17.";
    });
  }

  if (recoveryBtn && backupMessage) {
    recoveryBtn.addEventListener("click", function () {
      backupMessage.textContent = "Real database recovery will be added in Phase 17.";
    });
  }

  const saveSettingsBtn = document.getElementById("saveSettingsBtn");
  if (saveSettingsBtn) {
    saveSettingsBtn.addEventListener("click", function () {
      const settingsMessage = document.getElementById("settingsMessage");
      if (settingsMessage) {
        settingsMessage.textContent = "Saving settings to the database will be added in Phase 17.";
      }
    });
  }

});
