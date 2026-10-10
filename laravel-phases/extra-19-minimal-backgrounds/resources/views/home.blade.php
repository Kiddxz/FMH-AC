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
      <span class="hero-kicker">FMH Animal Clinic · Las Piñas &amp; Bacoor</span>
      <h1>Caring For Your Pets Like <span>Family</span></h1>
      <p>Book appointments, manage pet records, and experience quality veterinary care.</p>
      <div class="hero-actions">
        <a href="{{ route('login') }}?role=owner" class="bookbtn">Book Appointment</a>
        <a href="{{ route('guest.book') }}" class="bookbtn bookbtn-outline">Book Without an Account</a>
      </div>
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
    <div class="services-head">
      <span class="services-kicker">What we offer</span>
      <h2>Our Services</h2>
      <p>Complete care for your dogs and cats. Slide to see all our services and tap one to learn more.</p>
      <div class="service-filters" role="tablist" aria-label="Filter services">
        <button type="button" class="active" data-filter="all" role="tab" aria-selected="true">All services</button>
        <button type="button" data-filter="book" role="tab" aria-selected="false">Book online</button>
        <button type="button" data-filter="visit" role="tab" aria-selected="false">Visit the clinic</button>
      </div>
    </div>

    {{-- Slider: swipe (phone), drag or use the arrows (computer) to see the other services --}}
    <div class="slider-frame">
      <button type="button" class="slider-arrow prev" id="servicePrev" aria-label="Previous services"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
      <div class="service-slider" id="serviceSlider">
        @foreach ($clinic['services'] as $service)
          @php
            $bookId = $service['book'] ? ($bookable[$service['book']] ?? null) : null;
            $photo = $photoOf($service['slug']);
          @endphp
          <button type="button" class="service-card"
                  data-kind="{{ $bookId ? 'book' : 'visit' }}"
                  data-name="{{ $service['name'] }}"
                  data-text="{{ $service['text'] }}"
                  data-photo="{{ $photo }}"
                  data-book="{{ $bookId ? route('guest.book', ['service' => $bookId]) : '' }}">
            <span class="service-photo">
              @if ($photo)
                <img src="{{ $photo }}" alt="" loading="lazy" draggable="false">
              @else
                <span class="service-icon">{!! $icon($service['icon']) !!}</span>
              @endif
              <small class="{{ $bookId ? 'book-tag' : 'visit-tag' }}">{{ $bookId ? 'Book online' : 'Visit the clinic' }}</small>
            </span>
            <span class="service-body">
              <span class="service-name">{{ $service['name'] }}</span>
              <span class="service-teaser">{{ \Illuminate\Support\Str::words($service['text'], 9) }}</span>
              <span class="service-more">Learn more &rarr;</span>
            </span>
          </button>
        @endforeach
      </div>
      <button type="button" class="slider-arrow next" id="serviceNext" aria-label="Next services"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
    </div>
    <div class="slider-dots" id="serviceDots" aria-hidden="true"></div>
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
        {{-- Everything the pet owner needs to reach the clinic --}}
        <div class="contact-card">
          <h3>Get in Touch</h3>
          @foreach ($clinic['inquiries'] as $number)
            <a class="contact-row" href="tel:{{ preg_replace('/[^0-9+]/', '', $number) }}">
              <span class="contact-icon">{!! $icon('phone') !!}</span>
              <span><small>{{ str_starts_with($number, '09') ? 'Call or text' : 'Landline' }}</small>{{ $number }}</span>
            </a>
          @endforeach
          @if (! empty($clinic['email']))
            <a class="contact-row" href="mailto:{{ $clinic['email'] }}">
              <span class="contact-icon">{!! $icon('mail') !!}</span>
              <span><small>Email</small>{{ $clinic['email'] }}</span>
            </a>
          @endif
          @foreach ($clinic['socials'] as $social)
            @if ($social['url'])
              <a class="contact-row" href="{{ $social['url'] }}" target="_blank" rel="noopener">
            @else
              <div class="contact-row">
            @endif
                <span class="contact-icon">{!! $icon($social['type']) !!}</span>
                <span><small>{{ ucfirst($social['type']) }}</small>{{ $social['label'] }}</span>
            @if ($social['url'])
              </a>
            @else
              </div>
            @endif
          @endforeach
        </div>
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

      // Services slider: arrows, dots, and drag with the mouse (phones swipe by themselves)
      var slider = document.getElementById('serviceSlider');
      var prev = document.getElementById('servicePrev');
      var next = document.getElementById('serviceNext');
      var dots = document.getElementById('serviceDots');
      var pages = function () { return Math.max(1, Math.round(slider.scrollWidth / slider.clientWidth)); };
      var page = function () { return Math.round(slider.scrollLeft / slider.clientWidth); };
      var goTo = function (number) { slider.scrollTo({ left: number * slider.clientWidth, behavior: 'smooth' }); };
      var updateSlider = function () {
        var total = pages(), current = Math.min(page(), total - 1);
        if (dots.children.length !== total) {
          dots.innerHTML = '';
          for (var i = 0; i < total; i++) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.dataset.page = i;
            dot.addEventListener('click', function () { goTo(Number(this.dataset.page)); });
            dots.appendChild(dot);
          }
        }
        Array.prototype.forEach.call(dots.children, function (dot, i) { dot.classList.toggle('active', i === current); });
        prev.disabled = slider.scrollLeft <= 2;
        next.disabled = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 2;
        dots.style.visibility = total > 1 ? '' : 'hidden';
      };
      prev.addEventListener('click', function () { goTo(page() - 1); });
      next.addEventListener('click', function () { goTo(page() + 1); });
      slider.addEventListener('scroll', function () { window.requestAnimationFrame(updateSlider); });
      window.addEventListener('resize', updateSlider);

      var dragStart = null, dragged = false;
      slider.addEventListener('pointerdown', function (event) {
        if (event.pointerType !== 'mouse') return;
        dragStart = { x: event.clientX, left: slider.scrollLeft };
        dragged = false;
      });
      window.addEventListener('pointermove', function (event) {
        if (!dragStart) return;
        var moved = event.clientX - dragStart.x;
        if (!dragged && Math.abs(moved) > 6) { dragged = true; slider.classList.add('dragging'); }
        if (dragged) slider.scrollLeft = dragStart.left - moved;
      });
      window.addEventListener('pointerup', function () {
        if (!dragStart) return;
        dragStart = null;
        if (dragged) { slider.classList.remove('dragging'); goTo(page()); }
      });
      // a drag is not a click: do not open the pop-up after dragging
      slider.addEventListener('click', function (event) {
        if (dragged) { event.stopPropagation(); event.preventDefault(); dragged = false; }
      }, true);
      // after a filter button: back to the first page
      document.querySelectorAll('.service-filters button').forEach(function (tab) {
        tab.addEventListener('click', function () { slider.scrollLeft = 0; updateSlider(); });
      });
      updateSlider();

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
