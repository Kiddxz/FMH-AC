<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="home-page">
  <header class="home-header">
    <div class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</div>
    <nav class="navigator">
      <a href="#home">Home</a>
      <a href="#services">Services</a>
      <a href="#aboutus">About Us</a>
      <a href="#contact">Contact</a>
      <a href="#" id="loginLink">Login</a>
    </nav>
  </header>
  <div class="login-modal" id="loginModal">
    <div class="login-modal-card">
      <button class="close-login-modal" type="button" id="closeLoginModal">×</button>
      <img src="{{ asset('image/fmh-logo.png') }}" alt="FMH Animal Clinic logo" style="display: block; width: 130px; height: auto; margin: 0 auto 14px;">
      <h2>Log in</h2>
      <p class="login-modal-description">Choose your account type</p>
      <div class="account-type-options">
        <button type="button" class="account-type-card" data-role="owner">
          <div class="account-type-icon">🐶</div>
          <h3>Pet Owner</h3>
          <p>Manage your pets, appointments, and records.</p>
        </button>
        <button type="button" class="account-type-card" data-role="assistant">
          <div class="account-type-icon">🩺</div>
          <h3>Assistant</h3>
          <p>Manage appointments, pets, and pet records.</p>
        </button>
        <button type="button" class="account-type-card" data-role="admin">
          <div class="account-type-icon">🛡️</div>
          <h3>Admin</h3>
          <p>Manage appointments, pets, users, services, and system records.</p>
        </button>
        <button type="button" class="account-type-card" data-role="superadmin">
          <div class="account-type-icon">👑</div>
          <h3>Super Admin</h3>
          <p>Manage system users, reports, transactions, and inventory.</p>
        </button>
      </div>
      <button type="button" class="account-continue-btn" id="accountContinueBtn" disabled>Continue</button>
    </div>
  </div>
  <section id="home" class="hero">
    <div>
      <h1>Caring For Your Pets Like Family</h1>
      <p>Book appointments, manage pet records, and experience quality veterinary care.</p>
      <a href="{{ route('login') }}?role=owner" class="bookbtn">Book Appointment</a>
    </div>
  </section>
  <section id="services" class="services">
    <h2>Our Services</h2>
    <p>Professional veterinary services for your beloved pets.</p>
    <div>🐾 Consultation</div>
    <div>💉 Vaccination</div>
    <div>✂️ Grooming</div>
  </section>
  <section id="aboutus" class="contact">
    <h2>About Us</h2>
    <p>
      FMH Animal Clinic is dedicated to providing quality veterinary
      care for pets and their owners.
    </p>
    <p>Our goal is to make every pet feel safe, comfortable, and cared for.</p>
  </section>
  <section id="contact" class="contact">
    <h2>Contact Us</h2>
    <p>Las Piñas City</p>
    <p>0932-314-5969</p>
    <p>email</p>
  </section>
  <footer>© 2026 FMH Animal Clinic | All Rights Reserved</footer>
</body>
</html>
