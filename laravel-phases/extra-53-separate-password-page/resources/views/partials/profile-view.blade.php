{{-- "My Profile" page content, the same design for every user (Pet Owner, Staff, Vet/Admin, Super Admin).
     Use: @include('partials.profile-view', ['area' => 'staff', 'user' => $user])            (customer: 'area' => 'portal', plus 'customer' => $customer)
     Shows: a header card (initials, name, email, Edit Profile), the personal information,
     the account information and the Password & Security card (opens the separate Change Password page). --}}
@php
  $isVet = $user->hasRole(\App\Models\Role::VET_ADMIN);
  $initials = mb_strtoupper(mb_substr((string) $user->first_name, 0, 1) . mb_substr((string) $user->last_name, 0, 1)) ?: '?';
  $editUrl = route($area . '.profile.edit');
  $passwordUrl = route($area . '.password.edit');
  $roleName = $area === 'portal' ? 'Pet Owner' : ($user->role?->name ?? '—');

  // When the password was last changed (from the activity log)
  $passwordChangedAt = \App\Models\ActivityLog::where('user_id', $user->id)->where('action', 'password_changed')->latest('created_at')->value('created_at');
  $passwordChangedAt = $passwordChangedAt ? \Illuminate\Support\Carbon::parse($passwordChangedAt) : null;

  $icons = [
    'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
    'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    'pencil' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>',
  ];
  $svg = fn (string $name, int $size = 20) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';

  $personal = [
    ['Full Name', ($isVet ? 'Dr. ' : '') . $user->full_name],
    ['Email Address', $user->email],
    ['Mobile Number', $user->contact_number ?: '—'],
  ];
  if ($area === 'portal') {
    $personal[] = ['Address', $customer?->address ?: 'Not set'];
  }
  $account = [
    ['Role', $roleName],
    ['Account Status', null],   // shown as a colored label
    ['Member Since', $user->created_at?->format('F j, Y') ?? '—'],
    ['Last Login', $user->last_login_at?->format('F j, Y g:i A') ?? '—'],
  ];
@endphp
@once
<style>
  .pv-wrap { width: 100%; max-width: 1100px; margin: 0 auto; padding: 40px 28px 50px; box-sizing: border-box; }
  .pv-card { border: 1px solid #eee6db; border-radius: 16px; background: #fff; box-shadow: 0 2px 10px rgba(38, 54, 74, 0.05); }
  .pv-hero { overflow: hidden; margin-bottom: 20px; }
  .pv-cover { height: 110px; background: radial-gradient(circle at 85% -20%, rgba(255, 255, 255, 0.55) 0, rgba(255, 255, 255, 0) 45%), linear-gradient(120deg, #fff1df 0%, #ffe0b8 55%, #fbd09a 100%); }
  .pv-hero-body { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 18px; padding: 0 28px 24px; margin-top: -46px; }
  .pv-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 92px; height: 92px; border: 4px solid #fff; border-radius: 50%; background: #26364a; color: #fff; font-size: 30px; font-weight: 800; letter-spacing: 1px; box-shadow: 0 4px 14px rgba(38, 54, 74, 0.18); }
  .pv-who { flex: 1; min-width: 220px; padding-top: 54px; padding-bottom: 6px; }
  .pv-who h2 { margin: 0 0 6px; color: #26364a; font-size: 24px; font-weight: 800; overflow-wrap: anywhere; }
  .pv-email { color: #64748b; font-size: 14.5px; overflow-wrap: anywhere; }
  .pv-status { display: inline-flex; align-items: center; gap: 7px; }
  .pv-status::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #2e8b57; }
  .pv-status.inactive { color: #b03a37; }
  .pv-status.inactive::before { background: #d9534f; }
  .pv-section-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
  .pv-section-head h3 { margin: 0; }
  .pv-link { color: #c46f0c; font-size: 13.5px; font-weight: 700; text-decoration: none; }
  .pv-link:hover { text-decoration: underline; }
  .pv-security-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 46px; height: 46px; border-radius: 12px; background: #fff1df; color: #e89427; }
  .pv-security-text { display: flex; align-items: center; gap: 14px; }
  .pv-security h3 { margin: 0; color: #26364a; font-size: 17px; font-weight: 800; }
  .pv-actions { display: flex; flex-wrap: wrap; gap: 10px; padding-bottom: 4px; }
  .pv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 18px; border: 1px solid #eee6db; border-radius: 10px; background: #fff; color: #26364a; font-size: 14px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background 0.15s, border-color 0.15s, color 0.15s; }
  .pv-btn:hover { border-color: #e89427; color: #c46f0c; background: #fffaf3; }
  .pv-btn.primary { border-color: #e89427; background: #e89427; color: #fff; }
  .pv-btn.primary:hover { background: #d57d13; color: #fff; }
  .pv-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-bottom: 20px; }
  .pv-section { padding: 22px 24px; }
  .pv-section h3 { display: flex; align-items: center; gap: 10px; color: #26364a; font-size: 17px; font-weight: 800; }
  .pv-section h3 span { display: inline-flex; color: #e89427; }
  .pv-row { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-top: 1px solid #f3eee7; }
  .pv-row:first-of-type { border-top: none; }
  .pv-row dt { color: #8a8f98; font-size: 13.5px; font-weight: 600; }
  .pv-row dd { margin: 0; color: #26364a; font-size: 14.5px; font-weight: 600; text-align: right; overflow-wrap: anywhere; }
  .pv-security { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 24px; }
  .pv-security p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
  @media (max-width: 900px) {
    .pv-wrap { padding: 24px 16px 40px; }
    .pv-grid { grid-template-columns: 1fr; }
    .pv-hero-body { padding: 0 18px 18px; }
    .pv-who { padding-top: 0; }
    .pv-actions { width: 100%; }
    .pv-actions .pv-btn { flex: 1; }
    .pv-security .pv-btn { width: 100%; }
  }
  @media (max-width: 500px) {
    .pv-row { flex-direction: column; gap: 3px; }
    .pv-row dd { text-align: left; }
  }
</style>
@endonce
<main class="pv-wrap">
  <section class="pv-card pv-hero">
    <div class="pv-cover"></div>
    <div class="pv-hero-body">
      <span class="pv-avatar" aria-hidden="true">{{ $initials }}</span>
      <div class="pv-who">
        <h2>{{ $isVet ? 'Dr. ' : '' }}{{ $user->full_name }}</h2>
        <div class="pv-email">
          <span>{{ $user->email }}</span>
        </div>
      </div>
      <div class="pv-actions">
        <a href="{{ $editUrl }}" class="pv-btn primary">{!! $svg('pencil', 17) !!} Edit Profile</a>
      </div>
    </div>
  </section>

  <div class="pv-grid">
    <section class="pv-card pv-section">
      <div class="pv-section-head">
        <h3><span>{!! $svg('user') !!}</span> Personal Information</h3>
        <a href="{{ $editUrl }}" class="pv-link">Edit</a>
      </div>
      <dl style="margin: 0;">
        @foreach ($personal as [$label, $value])
          <div class="pv-row"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
      </dl>
    </section>
    <section class="pv-card pv-section">
      <div class="pv-section-head">
        <h3><span>{!! $svg('shield') !!}</span> Account</h3>
      </div>
      <dl style="margin: 0;">
        @foreach ($account as [$label, $value])
          <div class="pv-row"><dt>{{ $label }}</dt>
            <dd>
              @if ($value === null)
                <span class="pv-status {{ $user->isActive() ? '' : 'inactive' }}">{{ ucfirst($user->status) }}</span>
              @else
                {{ $value }}
              @endif
            </dd>
          </div>
        @endforeach
      </dl>
    </section>
  </div>

  <section class="pv-card pv-security">
    <div class="pv-security-text">
      <span class="pv-security-icon">{!! $svg('lock', 22) !!}</span>
      <div>
        <h3>Password &amp; Security</h3>
        <p>{{ $passwordChangedAt ? 'Last changed ' . $passwordChangedAt->format('F j, Y') . '.' : 'Keep your password private and change it from time to time.' }}</p>
      </div>
    </div>
    <a href="{{ $passwordUrl }}" class="pv-btn">Change Password</a>
  </section>
</main>
