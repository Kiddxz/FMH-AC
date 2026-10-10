<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FMH Animal Clinic</title>
  <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
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
      <a href="{{ route('guest.book') }}" class="bookbtn" style="margin-left: 10px; background: #fff; color: #e89427;">Book Without an Account</a>
    </div>
  </section>
@php
  $clinic = config('clinic');
  $icon = fn (string $name, string $class = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' . ($class ? ' class="' . $class . '"' : '') . '>' . ($clinic['icons'][$name] ?? '') . '</svg>';

  // Services that can be booked online (from the services list of the system)
  // (rescue: the page still opens before the database is set up)
  $bookable = rescue(fn () => \App\Models\Service::where('is_active', true)->pluck('id', 'name'), collect(), false);

  // A service photo is used when the file exists in public/image/services/
  $photoOf = function (string $slug) {
      foreach (['jpg', 'png', 'webp'] as $ext) {
          if (is_file(public_path("image/services/{$slug}.{$ext}"))) {
              return asset("image/services/{$slug}.{$ext}");
          }
      }

      return null;
  };

  // Clinic hours from Super Admin -> Settings, days with the same hours joined ("Mon - Sat")
  $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
  $schedules = rescue(fn () => \App\Models\ClinicSchedule::all()->keyBy('day_of_week'), collect(), false);
  $hours = [];
  $openDays = $schedules->where('is_open', true)->count() ?: 7;
  foreach ($days as $number => $label) {
      $s = $schedules->get($number);
      $text = $s && $s->is_open && $s->opens_at && $s->closes_at
          ? \Illuminate\Support\Carbon::parse($s->opens_at)->format('g:i A') . ' – ' . \Illuminate\Support\Carbon::parse($s->closes_at)->format('g:i A')
          : 'Closed';
      $last = array_key_last($hours);
      if ($last !== null && $hours[$last]['text'] === $text) {
          $hours[$last]['to'] = $label;
      } else {
          $hours[] = ['from' => $label, 'to' => $label, 'text' => $text];
      }
  }
@endphp
  <section id="services" class="landing-services">
    <div class="services-wrap">
      {{-- Left side: the pets from the clinic's banner --}}
      <aside class="services-side">
        <div class="pets-art">
          <span class="pets-circle"><img src="{{ asset('image/fmh-pets.png') }}" alt="A dog and three cats" class="pets-img"></span>
          <span class="pets-plus p1">+</span>
          <span class="pets-plus p2">+</span>
          <span class="pets-plus p3">+</span>
        </div>
        <span class="services-kicker">What we offer</span>
        <h2>Our Services</h2>
        <p>Complete care for your dogs and cats, from check-ups and vaccines to surgery and grooming. Tap a service to learn more.</p>
        <div class="services-stats">
          <div><strong>{{ count($clinic['services']) }}</strong><span>Services</span></div>
          <div><strong>{{ count($clinic['branches']) }}</strong><span>Branches</span></div>
          <div><strong>{{ $openDays }}</strong><span>Days a week</span></div>
        </div>
        <a href="{{ route('guest.book') }}" class="services-cta">Book an appointment</a>
      </aside>

      {{-- Right side: the services, with filters --}}
      <div class="services-main">
        <div class="service-filters" role="tablist" aria-label="Filter services">
          <button type="button" class="active" data-filter="all" role="tab" aria-selected="true">All services</button>
          <button type="button" data-filter="book" role="tab" aria-selected="false">Book online</button>
          <button type="button" data-filter="visit" role="tab" aria-selected="false">Visit the clinic</button>
        </div>
        <div class="service-grid">
          @foreach ($clinic['services'] as $service)
            @php $bookId = $service['book'] ? ($bookable[$service['book']] ?? null) : null; @endphp
            <button type="button" class="service-card"
                    data-kind="{{ $bookId ? 'book' : 'visit' }}"
                    data-name="{{ $service['name'] }}"
                    data-text="{{ $service['text'] }}"
                    data-photo="{{ $photoOf($service['slug']) }}"
                    data-book="{{ $bookId ? route('guest.book', ['service' => $bookId]) : '' }}">
              <span class="service-icon">{!! $icon($service['icon']) !!}</span>
              <span class="service-name">{{ $service['name'] }}</span>
              <span class="service-teaser">{{ \Illuminate\Support\Str::words($service['text'], 9) }}</span>
              <small class="{{ $bookId ? 'book-tag' : 'visit-tag' }}">{{ $bookId ? 'Book online' : 'Visit or call the clinic' }}</small>
              <span class="service-arrow" aria-hidden="true">&rarr;</span>
            </button>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  <dialog class="service-dialog" id="serviceDialog" aria-labelledby="serviceDialogTitle">
    <button type="button" class="service-dialog-close" id="serviceDialogClose" aria-label="Close">✕</button>
    <div class="service-dialog-photo" id="serviceDialogPhoto"></div>
    <div class="service-dialog-body">
      <h3 id="serviceDialogTitle"></h3>
      <p id="serviceDialogText"></p>
      <div class="service-dialog-note" id="serviceDialogNote">This service needs the veterinarian to see your pet first. Please visit the clinic or call {{ $clinic['inquiries'][0] }}.</div>
      <div class="service-dialog-actions">
        <a href="#" class="primary" id="serviceDialogBook">Book this service</a>
        <a href="{{ route('login') }}?role=owner" class="secondary" id="serviceDialogLogin">I have an account</a>
        <button type="button" class="secondary" id="serviceDialogBack">Back to services</button>
      </div>
    </div>
  </dialog>

  <section id="aboutus" class="landing-info">
    <div class="info-wrap">
      <div class="info-left">
        <div class="info-brand"><img src="{{ asset('image/fmh-logo.png') }}" alt="FMH Animal Clinic logo">FMH Animal Clinic</div>
        <p>FMH Animal Clinic is dedicated to providing quality veterinary care for pets and their owners. Our goal is to make every pet feel safe, comfortable and cared for.</p>
        <div class="info-photo" style="background-image: url('{{ asset('image/bg.jpg') }}'); background-position: center 30%;" role="img" aria-label="A veterinarian of FMH Animal Clinic with a puppy"></div>
        <div class="hours-box" id="hours">
          <h3>{!! $icon('clock') !!} Clinic Hours</h3>
          @foreach ($hours as $row)
            <div class="hours-row"><strong>{{ $row['from'] === $row['to'] ? $row['from'] : $row['from'] . ' – ' . $row['to'] }}</strong><span>{{ $row['text'] }}</span></div>
          @endforeach
        </div>
      </div>
      <div id="contact">
        <h2>Our Branches</h2>
        <div class="branch-list">
          @foreach ($clinic['branches'] as $branch)
            <div class="branch">
              @if (! empty($branch['photo']))
                <div class="branch-photo" style="background-image: url('{{ asset($branch['photo']) }}');" role="img" aria-label="FMH Animal Clinic {{ $branch['name'] }}"></div>
              @endif
              <div class="branch-body">
                <h3>FMH Animal Clinic – {{ $branch['name'] }}
                  @if ($branch['online_booking'])<span class="branch-tag">Online booking</span>@endif
                </h3>
                <p>{!! $icon('map-pin') !!} {{ $branch['address'] }}</p>
                <p>{!! $icon('phone') !!} {{ implode(' · ', $branch['phones']) }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="info-bar">
      <div class="socials">
        @foreach ($clinic['socials'] as $social)
          @if ($social['url'])
            <a href="{{ $social['url'] }}" target="_blank" rel="noopener">{!! $icon($social['type']) !!} {{ $social['label'] }}</a>
          @else
            <span>{!! $icon($social['type']) !!} {{ $social['label'] }}</span>
          @endif
        @endforeach
      </div>
      <span>{!! $icon('phone') !!} For inquiries: {{ implode(' | ', $clinic['inquiries']) }}</span>
    </div>
  </section>
  <footer>© {{ now()->year }} FMH Animal Clinic | All Rights Reserved</footer>
  <script>
    // Service cards: open the pop-up with the photo (or the icon), the text and the "Book" button
    (function () {
      var dialog = document.getElementById('serviceDialog');
      if (!dialog || !dialog.showModal) return;
      var photo = document.getElementById('serviceDialogPhoto');
      var book = document.getElementById('serviceDialogBook');
      var login = document.getElementById('serviceDialogLogin');
      var note = document.getElementById('serviceDialogNote');

      document.querySelectorAll('.service-card').forEach(function (card) {
        card.addEventListener('click', function () {
          document.getElementById('serviceDialogTitle').textContent = card.dataset.name;
          document.getElementById('serviceDialogText').textContent = card.dataset.text;
          photo.innerHTML = '';
          if (card.dataset.photo) {
            var img = document.createElement('img');
            img.src = card.dataset.photo;
            img.alt = card.dataset.name;
            photo.appendChild(img);
          } else {
            photo.innerHTML = card.querySelector('.service-icon').innerHTML;
          }
          var canBook = card.dataset.book !== '';
          book.style.display = canBook ? '' : 'none';
          login.style.display = canBook ? '' : 'none';
          note.style.display = canBook ? 'none' : '';
          if (canBook) book.href = card.dataset.book;
          dialog.showModal();
        });
      });

      // Filter buttons: All / Book online / Visit the clinic
      document.querySelectorAll('.service-filters button').forEach(function (tab) {
        tab.addEventListener('click', function () {
          document.querySelectorAll('.service-filters button').forEach(function (other) {
            other.classList.toggle('active', other === tab);
            other.setAttribute('aria-selected', other === tab ? 'true' : 'false');
          });
          document.querySelectorAll('.service-card').forEach(function (card) {
            card.hidden = tab.dataset.filter !== 'all' && card.dataset.kind !== tab.dataset.filter;
          });
        });
      });

      var close = function () { dialog.close(); };
      document.getElementById('serviceDialogClose').addEventListener('click', close);
      document.getElementById('serviceDialogBack').addEventListener('click', close);
      dialog.addEventListener('click', function (event) {
        if (event.target === dialog) close();   // click on the dark area
      });
    })();
  </script>
</body>
</html>
