{{-- "My Profile" page content, the same design for every user (Pet Owner, Staff, Vet/Admin, Super Admin).
     Use: @include('partials.profile-view', ['area' => 'staff', 'user' => $user])            (customer: 'area' => 'portal', plus 'customer' => $customer)
     Shows: a header card (initials, name, role, status, Edit / Change Password), a few numbers that fit the role,
     the personal information, the account information and a password reminder. --}}
@php
  $isVet = $user->hasRole(\App\Models\Role::VET_ADMIN);
  $initials = mb_strtoupper(mb_substr((string) $user->first_name, 0, 1) . mb_substr((string) $user->last_name, 0, 1)) ?: '?';
  $editUrl = route($area . '.profile.edit');
  $roleName = $area === 'portal' ? 'Pet Owner' : ($user->role?->name ?? '—');

  // A few numbers that fit each role
  $stats = match ($area) {
    'portal' => $customer ? [
      ['paw', 'Registered Pets', $customer->pets()->where('status', 'active')->count()],
      ['calendar', 'Upcoming Appointments', $customer->appointments()->whereDate('appointment_date', '>=', today())->whereIn('status', ['pending', 'confirmed'])->count()],
      ['waiver', 'Forms to Sign', $customer->waivers()->where('status', 'pending')->count()],
    ] : [],
    'staff' => [
      ['receipt', 'Bills Recorded', \App\Models\Transaction::where('cashier_id', $user->id)->count()],
      ['waiver', 'Waivers Prepared', \App\Models\Waiver::where('prepared_by', $user->id)->count()],
      ['flow', 'Patients Checked In Today', \App\Models\PatientVisit::where('handled_by', $user->id)->whereDate('visit_date', today())->count()],
    ],
    'admin' => [
      ['records', 'Pet Records Written', \App\Models\MedicalRecord::where('veterinarian_id', $user->id)->count()],
      ['calendar', 'My Upcoming Appointments', \App\Models\Appointment::where('veterinarian_id', $user->id)->whereDate('appointment_date', '>=', today())->whereIn('status', ['pending', 'confirmed'])->count()],
      ['waiver', 'Waivers Reviewed', \App\Models\Waiver::where('reviewed_by', $user->id)->count()],
    ],
    'superadmin' => [
      ['users', 'Active Accounts', \App\Models\User::where('status', 'active')->count()],
      ['backup', 'Backups Made', \App\Models\Backup::where('created_by', $user->id)->count()],
      ['clock', 'Last Backup', optional(\App\Models\Backup::latest()->first())->created_at?->diffForHumans() ?? 'None yet'],
    ],
    default => [],
  };

  $icons = [
    'paw' => '<circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="20" cy="16" r="2"/><path d="M9 10a5 5 0 0 1 5 5v3.5a3.5 3.5 0 0 1-6.84 1.045Q6.52 17.48 4.46 16.84A3.5 3.5 0 0 1 5.5 10Z"/>',
    'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
    'waiver' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
    'flow' => '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
    'records' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'backup' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
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
  .pv-title { margin: 0 0 6px; color: #26364a; font-size: 34px; font-weight: 800; }
  .pv-sub { margin: 0 0 24px; color: #64748b; font-size: 15px; }
  .pv-card { border: 1px solid #eee6db; border-radius: 16px; background: #fff; box-shadow: 0 2px 10px rgba(38, 54, 74, 0.05); }
  .pv-hero { overflow: hidden; margin-bottom: 20px; }
  .pv-cover { height: 96px; background: linear-gradient(120deg, #fff1df 0%, #ffe0b8 55%, #fbd09a 100%); }
  .pv-hero-body { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 18px; padding: 0 26px 22px; margin-top: -46px; }
  .pv-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 92px; height: 92px; border: 4px solid #fff; border-radius: 50%; background: #26364a; color: #fff; font-size: 30px; font-weight: 800; letter-spacing: 1px; box-shadow: 0 4px 14px rgba(38, 54, 74, 0.18); }
  .pv-who { flex: 1; min-width: 220px; padding-top: 54px; padding-bottom: 4px; }
  .pv-who h2 { margin: 0 0 6px; color: #26364a; font-size: 24px; font-weight: 800; overflow-wrap: anywhere; }
  .pv-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; color: #64748b; font-size: 14px; }
  .pv-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: #f4f1ec; color: #475569; font-size: 12.5px; font-weight: 700; }
  .pv-chip.role { background: #fff1df; color: #c46f0c; }
  .pv-chip.active { background: #e6f5e9; color: #287a43; }
  .pv-chip.inactive { background: #ffe5e5; color: #b03a37; }
  .pv-actions { display: flex; flex-wrap: wrap; gap: 10px; padding-bottom: 4px; }
  .pv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 18px; border: 1px solid #eee6db; border-radius: 10px; background: #fff; color: #26364a; font-size: 14px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background 0.15s, border-color 0.15s, color 0.15s; }
  .pv-btn:hover { border-color: #e89427; color: #c46f0c; background: #fffaf3; }
  .pv-btn.primary { border-color: #e89427; background: #e89427; color: #fff; }
  .pv-btn.primary:hover { background: #d57d13; color: #fff; }
  .pv-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
  .pv-stat { display: flex; align-items: center; gap: 14px; padding: 18px; }
  .pv-stat-icon { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 46px; height: 46px; border-radius: 12px; background: #fff1df; color: #e89427; }
  .pv-stat small { display: block; color: #64748b; font-size: 13px; }
  .pv-stat strong { display: block; margin-top: 2px; color: #26364a; font-size: 22px; font-weight: 800; }
  .pv-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-bottom: 20px; }
  .pv-section { padding: 22px 24px; }
  .pv-section h3 { display: flex; align-items: center; gap: 10px; margin: 0 0 14px; color: #26364a; font-size: 17px; font-weight: 800; }
  .pv-section h3 span { display: inline-flex; color: #e89427; }
  .pv-row { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-top: 1px solid #f3eee7; }
  .pv-row:first-of-type { border-top: none; }
  .pv-row dt { color: #8a8f98; font-size: 13.5px; font-weight: 600; }
  .pv-row dd { margin: 0; color: #26364a; font-size: 14.5px; font-weight: 600; text-align: right; overflow-wrap: anywhere; }
  .pv-security { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 24px; }
  .pv-security p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
  @media (max-width: 900px) {
    .pv-wrap { padding: 24px 16px 40px; }
    .pv-title { font-size: 28px; }
    .pv-stats { grid-template-columns: 1fr; }
    .pv-grid { grid-template-columns: 1fr; }
    .pv-hero-body { padding: 0 18px 18px; }
    .pv-who { padding-top: 0; }
    .pv-actions { width: 100%; }
    .pv-actions .pv-btn { flex: 1; }
  }
</style>
@endonce
<main class="pv-wrap">
  <h1 class="pv-title">My Profile</h1>
  <p class="pv-sub">Your personal and account information.</p>

  <section class="pv-card pv-hero">
    <div class="pv-cover"></div>
    <div class="pv-hero-body">
      <span class="pv-avatar" aria-hidden="true">{{ $initials }}</span>
      <div class="pv-who">
        <h2>{{ $isVet ? 'Dr. ' : '' }}{{ $user->full_name }}</h2>
        <div class="pv-chips">
          <span class="pv-chip role">{{ $roleName }}</span>
          <span class="pv-chip {{ $user->isActive() ? 'active' : 'inactive' }}">{{ ucfirst($user->status) }}</span>
          <span>{{ $user->email }}</span>
        </div>
      </div>
      <div class="pv-actions">
        <a href="{{ $editUrl }}" class="pv-btn primary">{!! $svg('pencil', 17) !!} Edit Profile</a>
        <a href="{{ $editUrl }}#change-password" class="pv-btn">{!! $svg('lock', 17) !!} Change Password</a>
      </div>
    </div>
  </section>

  @if (count($stats))
    <section class="pv-stats">
      @foreach ($stats as [$icon, $label, $value])
        <div class="pv-card pv-stat">
          <span class="pv-stat-icon">{!! $svg($icon, 22) !!}</span>
          <div><small>{{ $label }}</small><strong>{{ $value }}</strong></div>
        </div>
      @endforeach
    </section>
  @endif

  <div class="pv-grid">
    <section class="pv-card pv-section">
      <h3><span>{!! $svg('user') !!}</span> Personal Information</h3>
      <dl style="margin: 0;">
        @foreach ($personal as [$label, $value])
          <div class="pv-row"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
      </dl>
    </section>
    <section class="pv-card pv-section">
      <h3><span>{!! $svg('shield') !!}</span> Account</h3>
      <dl style="margin: 0;">
        @foreach ($account as [$label, $value])
          <div class="pv-row"><dt>{{ $label }}</dt>
            <dd>
              @if ($value === null)
                <span class="pv-chip {{ $user->isActive() ? 'active' : 'inactive' }}">{{ ucfirst($user->status) }}</span>
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
    <div>
      <h3 style="display: flex; align-items: center; gap: 10px; margin: 0; color: #26364a; font-size: 17px; font-weight: 800;"><span style="display: inline-flex; color: #e89427;">{!! $svg('lock') !!}</span> Password &amp; Security</h3>
      <p>Keep your password private. Change it right away if you think someone else knows it.</p>
    </div>
    <a href="{{ $editUrl }}#change-password" class="pv-btn">Change Password</a>
  </section>
</main>
