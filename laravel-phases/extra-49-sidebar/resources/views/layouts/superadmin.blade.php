<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Super Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-sidebar.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
@php
    // Side menu of the Super Admin panel: [label, route, routes that make it active, icon]
    $menu = [
        ['title' => null, 'items' => [
            ['Dashboard', 'superadmin.dashboard', ['superadmin.dashboard'], 'dashboard'],
        ]],
        ['title' => 'Accounts', 'items' => [
            ['Users', 'superadmin.users.index', ['superadmin.users.*', 'superadmin.roles.*'], 'users'],
        ]],
        ['title' => 'Clinic Records', 'items' => [
            ['Appointments', 'superadmin.appointments', ['superadmin.appointments'], 'calendar'],
            ['Pet Records', 'superadmin.pet-records', ['superadmin.pet-records'], 'records'],
            ['Waiver & Consent', 'superadmin.waivers.index', ['superadmin.waivers.*'], 'waiver'],
        ]],
        ['title' => 'Business', 'items' => [
            ['Sales & Transactions', 'superadmin.transactions', ['superadmin.transactions*'], 'payments'],
            ['Inventory', 'superadmin.inventory', ['superadmin.inventory'], 'inventory'],
            ['Reports', 'superadmin.reports', ['superadmin.reports'], 'reports'],
        ]],
        ['title' => 'System', 'items' => [
            ['Activity Logs', 'superadmin.activity-logs', ['superadmin.activity-logs'], 'activity'],
            ['Backup & Recovery', 'superadmin.backups', ['superadmin.backups'], 'backup'],
            ['Settings', 'superadmin.settings', ['superadmin.settings'], 'settings'],
        ]],
    ];
@endphp
<body class="@yield('body_class', 'superadmin-page') fmh-app has-sidebar">
    {{-- Side menu on the left (like the PayMongo dashboard): it can be made small with the button in the top bar.
         On a phone it opens with the Menu button (see partials/sidebar and animal.js). --}}
    @include('partials.sidebar', ['area' => 'superadmin', 'menu' => $menu])
    <div class="app-main">
        <div class="topbar">
            <button type="button" class="sb-collapse" title="Hide or show the menu" aria-label="Hide or show the menu" aria-controls="panelMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
            </button>
            <a href="{{ route('superadmin.dashboard') }}" class="topbar-logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="">FMH Animal Clinic</a>
            <div class="topbar-right">
                @include('partials.inventory-bell', ['area' => 'superadmin'])
                @include('partials.user-menu', ['profileRoute' => 'superadmin.profile', 'logoutRoute' => 'superadmin.logout'])
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="panelMenu">☰ Menu</button>
            </div>
        </div>
        @include('partials.phase-notice')
        @if (session('status') || $errors->any())
            <div style="max-width: 1400px; margin: 25px auto 0; padding: 0 20px;">
                @include('partials.alerts')
            </div>
        @endif
        <div class="app-content">
            @yield('content')
        </div>
        @hasSection('footer')
            <footer>@yield('footer')</footer>
        @endif
    </div>
    @stack('scripts')
</body>
</html>
