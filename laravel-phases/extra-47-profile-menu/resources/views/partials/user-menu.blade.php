{{-- Small round profile button (initials). Clicking it opens a box with the name, email and the Profile / Log out links.
     Use: @include('partials.user-menu', ['profileRoute' => 'admin.profile', 'logoutRoute' => 'admin.logout'])
     Without 'logoutRoute' (Pet Owner portal) "Log out" sends the log out form right away.
     On a phone: inside the Staff / Admin / Super Admin "Menu" it is a profile card with two buttons;
     in the Pet Owner portal the round button sits at the top right of the page. --}}
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

  @media (max-width: 900px) {
    /* Staff / Admin / Super Admin: profile card inside the opened "Menu" */
    .panel-user { position: relative; display: block !important; margin-top: 12px !important; padding: 0 !important; border-top: none !important; }
    .panel-user .notif { position: absolute; top: 16px; right: 14px; z-index: 2; }
    .panel-user .user-menu-btn { display: none; }
    .panel-user .user-menu-box { position: static; display: block; width: 100%; padding: 0; border-radius: 14px; background: #fffdfa; box-shadow: none; }
    .panel-user .user-menu-avatar { display: inline-flex; width: 46px; height: 46px; font-size: 16px; }
    .panel-user .user-menu-head { padding: 14px 64px 14px 14px; }
    .panel-user .user-menu-links { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px 14px 14px; }
    .panel-user .user-menu-box .user-menu-item { justify-content: center; gap: 8px; height: 44px; padding: 0 12px; border: 1px solid #eee6db !important; border-radius: 10px; background: #fff; font-weight: 600; }
    .panel-user .user-menu-box .user-menu-item.active { border-color: #e89427 !important; background: #fff1df; }
    .panel-user .user-menu-logout { margin-top: 0; color: #b03a37 !important; }
    .panel-user .user-menu-box .user-menu-logout:hover { background: #fff1f1; color: #b03a37 !important; }

    /* Pet Owner portal: round button at the top right, the box floats under it */
    .app-header { position: relative; }
    .app-header > .logo { text-align: left !important; padding-right: 54px; box-sizing: border-box; }
    .app-header > .user-menu { position: absolute; top: 14px; right: 16px; }
    .app-header .user-menu-btn { width: 38px; height: 38px; font-size: 13px; }
    .app-header .user-menu-box { width: min(280px, calc(100vw - 32px)); }
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
