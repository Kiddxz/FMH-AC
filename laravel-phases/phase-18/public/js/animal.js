/*
  FMH Animal Clinic - animal.js (Phase 18 version)

  This file only adds small page behaviors (popup, phone menu).
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

});
