{{-- Small round profile button (initials). Clicking it opens a box with the name, email and the Profile / Log out links.
     Use: @include('partials.user-menu', ['profileRoute' => 'admin.profile', 'logoutRoute' => 'admin.logout'])
     Without 'logoutRoute' (Pet Owner portal) "Log out" sends the log out form right away. --}}
@php
  $me = auth()->user();
  $initials = mb_strtoupper(mb_substr((string) $me->first_name, 0, 1) . mb_substr((string) $me->last_name, 0, 1));
  $profileActive = request()->routeIs($profileRoute . '*');
@endphp
@once
<style>
  .user-menu { position: relative; }
  .user-menu-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; border: 2px solid transparent; border-radius: 50%; background: #26364a; color: #fff; font-size: 14px; font-weight: 700; letter-spacing: 0.5px; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
  .user-menu-btn:hover, .user-menu.open .user-menu-btn { border-color: #e89427; box-shadow: 0 0 0 3px #fff1df; }
  .user-menu-box { position: absolute; top: calc(100% + 10px); right: 0; z-index: 1000; display: none; width: 260px; padding: 6px 0; border: 1px solid #eee6db; border-radius: 12px; background: #fff; box-shadow: 0 12px 30px rgba(38, 54, 74, 0.16); text-align: left; }
  .user-menu.open .user-menu-box { display: block; }
  .user-menu-head { padding: 12px 16px 12px; border-bottom: 1px solid #f1ebe3; }
  .user-menu-head strong { display: block; color: #26364a; font-size: 14px; text-transform: uppercase; letter-spacing: 0.3px; overflow-wrap: anywhere; }
  .user-menu-head span { display: block; margin-top: 3px; color: #2e8b57; font-size: 13px; overflow-wrap: anywhere; }
  .user-menu-head small { display: block; margin-top: 2px; color: #8a8f98; font-size: 12px; }
  .user-menu-box a, .user-menu-box button.user-menu-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 11px 16px; border: none; background: none; color: #26364a !important; font-family: inherit; font-size: 14px; font-weight: 500; text-align: left; text-decoration: none; cursor: pointer; box-sizing: border-box; }
  .user-menu-box a:hover, .user-menu-box button.user-menu-item:hover, .user-menu-box a.active { background: #faf6f0; color: #c46f0c !important; }
  .user-menu-box svg { flex-shrink: 0; width: 18px; height: 18px; }
  .user-menu-line { height: 1px; margin: 6px 0; background: #f1ebe3; }
  .user-menu-box form { margin: 0; }
  /* phone: the box opens under the button instead of floating */
  @media (max-width: 900px) {
    .panel-user { align-items: flex-start !important; }
    .user-menu { flex: 1; display: flex; flex-direction: column; align-items: flex-end; }
    .user-menu-box { position: static; width: 100%; margin-top: 10px; box-shadow: none; }
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
  <button type="button" class="user-menu-btn" aria-haspopup="true" aria-expanded="false" title="{{ $me->full_name }}">{{ $initials ?: '?' }}</button>
  <div class="user-menu-box">
    <div class="user-menu-head">
      <strong>{{ $me->full_name }}</strong>
      <span>{{ $me->email }}</span>
      @if ($me->role)<small>{{ $me->role->name }}</small>@endif
    </div>
    <a href="{{ route($profileRoute) }}" class="{{ $profileActive ? 'active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <div class="user-menu-line"></div>
    @if (! empty($logoutRoute))
      <a href="{{ route($logoutRoute) }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
        Log out
      </a>
    @else
      <form action="{{ route('logout') }}" method="post">
        @csrf
        <button type="submit" class="user-menu-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
          Log out
        </button>
      </form>
    @endif
  </div>
</div>
