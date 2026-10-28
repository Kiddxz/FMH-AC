{{-- Side menu of the Staff, Admin and Super Admin panels (style: public/css/fmh-sidebar.css).
     Use: @include('partials.sidebar', ['area' => 'admin', 'menu' => $menu])
     $menu = groups of links: [['title' => 'Clinic', 'items' => [['Label', 'route.name', ['active.route.*'], 'icon'], ...]], ...]
     On a computer the "hide menu" button makes it small (only icons); the browser remembers it.
     On a small laptop (901px - 1200px) it is small by default and the button opens it over the page.
     On a phone it slides in from the left with the Menu button, with the user at the top and Profile / Log out at the bottom. --}}
@php
  $me = auth()->user();
  $initials = mb_strtoupper(mb_substr((string) $me->first_name, 0, 1) . mb_substr((string) $me->last_name, 0, 1)) ?: '?';
  $panelName = ['staff' => 'Staff Panel', 'admin' => 'Admin Panel', 'superadmin' => 'Super Admin Panel'][$area] ?? 'Panel';
  $icons = [
    'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
    'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    'flow' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
    'paw' => '<circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="20" cy="16" r="2"/><path d="M9 10a5 5 0 0 1 5 5v3.5a3.5 3.5 0 0 1-6.84 1.045Q6.52 17.48 4.46 16.84A3.5 3.5 0 0 1 5.5 10Z"/>',
    'records' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    'waiver' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'services' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
    'payments' => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
    'pos' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
    'inventory' => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"/><path d="m7.5 4.27 9 5.15"/>',
    'reports' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'activity' => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
    'backup' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
    'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    'profile' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
  ];
  $svg = fn (string $name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
@endphp
<script>
  // Small side menu: remembered by this browser (set before the menu is drawn, so it does not jump)
  try { if (localStorage.getItem('fmh-sidebar') === 'small') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
</script>
<aside class="sidebar" id="panelMenu" aria-label="Main menu">
  <a href="{{ route($area . '.dashboard') }}" class="sb-brand" title="FMH Animal Clinic">
    <img src="{{ asset('image/fmh-logo-icon.png') }}" alt="">
    <span>FMH Animal Clinic<small>{{ $panelName }}</small></span>
  </a>

  <div class="sb-phone-user">
    <span class="sb-avatar" aria-hidden="true">{{ $initials }}</span>
    <div>
      <strong>{{ $me->full_name }}</strong>
      <span>{{ $me->email }}</span>
      @if ($me->role)<small>{{ $me->role->name }}</small>@endif
    </div>
  </div>

  <nav class="sb-scroll panel-nav-list">
    @foreach ($menu as $group)
      <div class="sb-group">
        @if (! empty($group['title']))
          <p class="sb-group-title">{{ $group['title'] }}</p>
        @endif
        @foreach ($group['items'] as [$label, $route, $active, $icon])
          <a href="{{ route($route) }}" class="sb-link {{ request()->routeIs(...$active) ? 'active' : '' }}" title="{{ $label }}"
             @if (request()->routeIs(...$active)) aria-current="page" @endif>
            {!! $svg($icon) !!}<span class="sb-label">{{ $label }}</span>
          </a>
        @endforeach
      </div>
    @endforeach
  </nav>

  <div class="sb-phone-links">
    <a href="{{ route($area . '.profile') }}" class="sb-link {{ request()->routeIs($area . '.profile*') ? 'active' : '' }}">{!! $svg('profile') !!}<span class="sb-label">Profile</span></a>
    {{-- logs out right away (no "Are you sure?" page) --}}
    <form action="{{ route('logout') }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="sb-link sb-logout">{!! $svg('logout') !!}<span class="sb-label">Log out</span></button>
    </form>
  </div>
</aside>
<div class="sb-backdrop" aria-hidden="true"></div>

@push('scripts')
<script>
  (function () {
    // Computer: the "hide menu" button makes the side menu small (only icons) or big again.
    // Small laptop (901px - 1200px): the menu is small by default and the button opens it over the page.
    var html = document.documentElement;
    var collapse = document.querySelector('.sb-collapse');
    var midScreen = window.matchMedia('(min-width: 901px) and (max-width: 1200px)');
    function isBig() {
      return midScreen.matches ? html.classList.contains('sb-expanded') : ! html.classList.contains('sb-collapsed');
    }
    function showState() {
      if (collapse) collapse.setAttribute('aria-expanded', isBig() ? 'true' : 'false');
    }
    if (collapse) {
      showState();
      collapse.addEventListener('click', function (event) {
        event.stopPropagation();
        if (midScreen.matches) {
          html.classList.toggle('sb-expanded');
        } else {
          var nowSmall = html.classList.toggle('sb-collapsed');
          try { localStorage.setItem('fmh-sidebar', nowSmall ? 'small' : 'big'); } catch (e) {}
        }
        showState();
      });
      // small laptop: a click outside the opened menu closes it
      document.addEventListener('click', function (event) {
        if (midScreen.matches && html.classList.contains('sb-expanded') && ! event.target.closest('#panelMenu')) {
          html.classList.remove('sb-expanded');
          showState();
        }
      });
      midScreen.addEventListener('change', function () { html.classList.remove('sb-expanded'); showState(); });
    }

    // Phone: clicking the dark area or pressing Esc closes the menu (the Menu button itself is in animal.js)
    function closePhoneMenu() {
      var menu = document.getElementById('panelMenu');
      var toggle = document.querySelector('.nav-toggle');
      if (menu && menu.classList.contains('open') && toggle) toggle.click();
    }
    var backdrop = document.querySelector('.sb-backdrop');
    if (backdrop) backdrop.addEventListener('click', closePhoneMenu);
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      closePhoneMenu();
      if (html.classList.contains('sb-expanded')) { html.classList.remove('sb-expanded'); showState(); }
    });
  })();
</script>
@endpush
