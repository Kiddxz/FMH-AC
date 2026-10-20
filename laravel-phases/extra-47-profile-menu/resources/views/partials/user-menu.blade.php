{{-- Small round profile button (initials). Clicking it opens a box with the name, email and the Profile / Log out links.
     Use: @include('partials.user-menu', ['profileRoute' => 'admin.profile', 'logoutRoute' => 'admin.logout'])
     Without 'logoutRoute' (Pet Owner portal) "Log out" sends the log out form right away.
     On a phone the top bar shows the bell and a "Menu" button; the opened Menu shows the user at the top,
     the pages in the middle and Profile / Log out at the bottom. --}}
@php
  $me = auth()->user();
  $initials = mb_strtoupper(mb_substr((string) $me->first_name, 0, 1) . mb_substr((string) $me->last_name, 0, 1)) ?: '?';
  $profileActive = request()->routeIs($profileRoute . '*');
@endphp
@once
<style>
  .user-menu { position: relative; }
  .user-menu-btn, .user-menu-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: #26364a; color: #fff; font-size: 14px; font-weight: 700; letter-spacing: 0.5px; }
  .user-menu-btn { padding: 0; border: 2px solid transparent; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
  .user-menu-btn:hover, .user-menu.open .user-menu-btn { border-color: #e89427; box-shadow: 0 0 0 3px #fff1df; }
  .user-menu-avatar { display: none; }
  .user-menu-box { position: absolute; top: calc(100% + 10px); right: 0; z-index: 1000; display: none; width: 260px; padding: 6px 0; border: 1px solid #eee6db; border-radius: 12px; background: #fff; box-shadow: 0 12px 30px rgba(38, 54, 74, 0.16); text-align: left; }
  .user-menu.open .user-menu-box { display: block; }
  .user-menu-head { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1ebe3; }
  .user-menu-who { min-width: 0; }
  .user-menu-who strong { display: block; color: #26364a; font-size: 14px; text-transform: uppercase; letter-spacing: 0.3px; overflow-wrap: anywhere; }
  .user-menu-who span { display: block; margin-top: 3px; color: #2e8b57; font-size: 13px; overflow-wrap: anywhere; }
  .user-menu-who small { display: block; margin-top: 2px; color: #8a8f98; font-size: 12px; }
  .user-menu-links { padding-top: 6px; }
  .user-menu-links form { margin: 0; }
  .user-menu-box .user-menu-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 11px 16px; border: none; background: none; color: #26364a !important; font-family: inherit; font-size: 14px; font-weight: 500; text-align: left; text-decoration: none; cursor: pointer; box-sizing: border-box; }
  .user-menu-box .user-menu-item:hover, .user-menu-box .user-menu-item.active { background: #faf6f0; color: #c46f0c !important; }
  .user-menu-box .user-menu-item svg { flex-shrink: 0; width: 18px; height: 18px; }
  .user-menu-logout { border-top: 1px solid #f1ebe3 !important; margin-top: 6px; }

  /* the bell sits in the top bar, right before the round profile button (and before "Menu" on a phone) */
  .panel-bell { order: 2; margin-left: auto; display: flex; align-items: center; }
  .panel-bar .panel-user { margin-left: 0; }
  .portal-menu { display: contents; }

  @media (max-width: 900px) {
    /* top bar: logo ... bell, Menu */
    .panel-bar { flex-wrap: wrap; column-gap: 8px; padding: 0 16px !important; }
    .panel-bar .logo { font-size: 18px !important; white-space: nowrap; }
    /* the Menu button is only an icon on a phone: ☰ when closed, ✕ when open (the text stays for screen readers) */
    .panel-bar .nav-toggle, .app-header .nav-toggle { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 40px; height: 40px; margin-left: 0; padding: 0; border-radius: 10px; font-size: 0; line-height: 1; }
    .panel-bar .nav-toggle::before, .app-header .nav-toggle::before { content: "\2630"; font-size: 20px; }
    .panel-bar .nav-toggle[aria-expanded="true"]::before, .app-header .nav-toggle[aria-expanded="true"]::before { content: "\2715"; font-size: 18px; }
    .panel-bar { flex-wrap: wrap; }
    .panel-bar .logo, .app-header .logo { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .app-header { flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; }
    .app-header .logo { width: auto !important; font-size: 18px; text-align: left !important; white-space: nowrap; }
    .app-header .nav-toggle { order: 2; margin-left: auto; }
    .portal-menu { display: none; order: 3; width: 100%; padding: 6px 0 4px; border-top: 1px solid #f1ebe3; }
    .app-header .app-nav { flex-direction: column; align-items: stretch; width: 100%; gap: 2px; }
    .app-header .app-nav a { padding: 11px 14px; font-size: 15px; text-align: left; }

    /* opened Menu: user on top, the pages in the middle, Profile / Log out at the bottom */
    .panel-menu.open, .portal-menu.open { display: flex; flex-direction: column; }
    .panel-menu .panel-user, .panel-menu .user-menu, .panel-menu .user-menu-box,
    .portal-menu .user-menu, .portal-menu .user-menu-box { display: contents; }
    .panel-menu .user-menu-btn, .portal-menu .user-menu-btn { display: none; }
    .panel-menu .user-menu-head, .portal-menu .user-menu-head { order: -1; margin: 6px 0 10px; padding: 14px; border: 1px solid #eee6db; border-radius: 14px; background: #fffdfa; }
    .panel-menu .user-menu-avatar, .portal-menu .user-menu-avatar { display: inline-flex; width: 46px; height: 46px; font-size: 16px; }
    .panel-menu .panel-nav, .portal-menu .app-nav { order: 0; }
    .panel-menu .user-menu-links, .portal-menu .user-menu-links { order: 1; margin-top: 10px; padding-top: 8px; border-top: 1px solid #f1ebe3; }
    .panel-menu .user-menu-links .user-menu-item, .portal-menu .user-menu-links .user-menu-item { padding: 12px 14px; border-radius: 8px; color: #26364a !important; font-size: 15px; font-weight: 600; }
    .panel-menu .user-menu-links .user-menu-item:hover, .portal-menu .user-menu-links .user-menu-item:hover { background: #fff5e8; }
    .panel-menu .user-menu-logout, .portal-menu .user-menu-logout { margin-top: 0; border-top: none !important; color: #b03a37 !important; }
    .panel-menu .user-menu-links .user-menu-logout:hover, .portal-menu .user-menu-links .user-menu-logout:hover { background: #fff1f1; }
  }
  /* very narrow phones: smaller logo so the logo, bell and Menu stay on one line */
  @media (max-width: 380px) {
    .panel-bar { column-gap: 4px; padding: 0 12px !important; }
    .panel-bar .logo, .app-header .logo { font-size: 16px !important; }
    .app-header { padding-left: 12px !important; padding-right: 12px !important; }
  }
</style>
<script>
  // Open / close the profile box; it closes when clicking outside it or pressing Esc
  document.addEventListener('click', function (event) {
    var button = event.target.closest('.user-menu-btn');
    document.querySelectorAll('.user-menu.open').forEach(function (menu) {
      if (! menu.contains(event.target)) {
        menu.classList.remove('open');
        menu.querySelector('.user-menu-btn').setAttribute('aria-expanded', 'false');
      }
    });
    if (button) {
      var open = button.closest('.user-menu').classList.toggle('open');
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.user-menu.open').forEach(function (menu) {
      menu.classList.remove('open');
      menu.querySelector('.user-menu-btn').setAttribute('aria-expanded', 'false');
    });
  });
</script>
@endonce
<div class="user-menu">
  <button type="button" class="user-menu-btn" aria-haspopup="true" aria-expanded="false" title="{{ $me->full_name }}">{{ $initials }}</button>
  <div class="user-menu-box">
    <div class="user-menu-head">
      <span class="user-menu-avatar" aria-hidden="true">{{ $initials }}</span>
      <div class="user-menu-who">
        <strong>{{ $me->full_name }}</strong>
        <span>{{ $me->email }}</span>
        @if ($me->role)<small>{{ $me->role->name }}</small>@endif
      </div>
    </div>
    <div class="user-menu-links">
      <a href="{{ route($profileRoute) }}" class="user-menu-item {{ $profileActive ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile
      </a>
      @if (! empty($logoutRoute))
        <a href="{{ route($logoutRoute) }}" class="user-menu-item user-menu-logout">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
          Log out
        </a>
      @else
        <form action="{{ route('logout') }}" method="post">
          @csrf
          <button type="submit" class="user-menu-item user-menu-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
            Log out
          </button>
        </form>
      @endif
    </div>
  </div>
</div>
