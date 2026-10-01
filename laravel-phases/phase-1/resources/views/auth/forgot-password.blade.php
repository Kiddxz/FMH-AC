<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>
    FMH Animal Clinic | Forgot Password
  </title>


  <link rel="stylesheet" href="{{ asset('animal.css') }}">

</head>


<body class="account-page">


  <header>

    <h2 class="logo">
      🐾 FMH Animal Clinic
    </h2>

  </header>


  <main class="account-container">

    <div class="account-card">

      <div class="forgot-icon">

        🔐

      </div>


      <h1>
        Forgot Password?
      </h1>

@include('partials.phase-notice')


      <p class="account-description">
        Enter your email address and we will help you reset your password.
      </p>


      <form
        action="{{ route('password.email') }}"
        method="post">
            @csrf

        <div class="form-group">

          <label for="email">
            Email Address
          </label>


          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
            autocomplete="email"
            required
          >

        </div>


        <button
          class="resetbtn"
          type="submit"
        >
          Send Reset Link
        </button>

      </form>


      <div class="divider">

        <span>
          OR
        </span>

      </div>


      <div class="relog">

        <p>

          Remember your password?

          <a href="{{ route('login') }}">
            Back to Login
          </a>

        </p>

      </div>

    </div>

  </main>


</body>

</html>
