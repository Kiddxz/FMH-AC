{{-- "Something went wrong" page (Phase 18). Shown instead of the technical error screen when APP_DEBUG=false.
     It does not use the database or the login, because the error itself may come from the database. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic | Something Went Wrong</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
</head>
<body class="account-page" style="background: #faf6f0;">
  <header>
    <h2 class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</h2>
  </header>
  <main class="account-container">
    <div class="account-card" style="text-align: center;">
      <div class="forgot-icon">⚠️</div>
      <h1>Something Went Wrong</h1>
      <p class="account-description">The system could not finish your request. Please go back and try again. If it happens again, tell the clinic's system administrator.</p>
      <a href="{{ url('/') }}" class="resetbtn" style="text-decoration: none;">Back to Home</a>
    </div>
  </main>
</body>
</html>
