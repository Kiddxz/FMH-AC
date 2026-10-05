<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Super Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'superadmin-page') fmh-app">
    {{-- Phase 18: one menu style for Staff, Admin and Super Admin. Logo and user on top, menu below.
         On a phone the menu is folded under the "Menu" button (see animal.js). --}}
    <header class="panel-header">
        <div class="panel-bar">
            <a href="{{ route('superadmin.dashboard') }}" class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="panelMenu">☰ Menu</button>
            <div class="panel-menu" id="panelMenu">
                <nav class="panel-nav" aria-label="Main menu">
                    <a href="{{ route('superadmin.dashboard') }}" class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('superadmin.users.index') }}" class="{{ request()->routeIs('superadmin.users.*', 'superadmin.roles.*') ? 'active' : '' }}">Users</a>
                    <a href="{{ route('superadmin.appointments') }}" class="{{ request()->routeIs('superadmin.appointments') ? 'active' : '' }}">Appointments</a>
                    <a href="{{ route('superadmin.pet-records') }}" class="{{ request()->routeIs('superadmin.pet-records') ? 'active' : '' }}">Pet Records</a>
                    <a href="{{ route('superadmin.waivers.index') }}" class="{{ request()->routeIs('superadmin.waivers.*') ? 'active' : '' }}">Waiver &amp; Consent</a>
                    <a href="{{ route('superadmin.transactions') }}" class="{{ request()->routeIs('superadmin.transactions*') ? 'active' : '' }}">Sales &amp; Transactions</a>
                    <a href="{{ route('superadmin.inventory') }}" class="{{ request()->routeIs('superadmin.inventory') ? 'active' : '' }}">Inventory</a>
                    <a href="{{ route('superadmin.reports') }}" class="{{ request()->routeIs('superadmin.reports') ? 'active' : '' }}">Reports</a>
                    <a href="{{ route('superadmin.activity-logs') }}" class="{{ request()->routeIs('superadmin.activity-logs') ? 'active' : '' }}">Activity Logs</a>
                    <a href="{{ route('superadmin.backups') }}" class="{{ request()->routeIs('superadmin.backups') ? 'active' : '' }}">Backup &amp; Recovery</a>
                    <a href="{{ route('superadmin.settings') }}" class="{{ request()->routeIs('superadmin.settings') ? 'active' : '' }}">Settings</a>
                </nav>
                <div class="panel-user">
                    <span class="panel-user-name">
                        <strong>{{ auth()->user()->full_name }}</strong>
                        <small>{{ auth()->user()->role?->name }}</small>
                    </span>
                    <a href="{{ route('superadmin.profile') }}" class="{{ request()->routeIs('superadmin.profile*') ? 'active' : '' }}">👤 Profile</a>
                    <a href="{{ route('superadmin.logout') }}" class="panel-logout">Logout</a>
                </div>
            </div>
        </div>
    </header>
    @include('partials.phase-notice')
    @if (session('status') || $errors->any())
        <div style="max-width: 1400px; margin: 25px auto 0; padding: 0 20px;">
            @include('partials.alerts')
        </div>
    @endif
    @yield('content')
    @hasSection('footer')
        <footer>@yield('footer')</footer>
    @endif
    @stack('scripts')
</body>
</html>
