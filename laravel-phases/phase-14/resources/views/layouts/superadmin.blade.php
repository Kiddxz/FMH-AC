<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Super Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'superadmin-page')">
    <header class="superadmin-header">
        <div class="logo">🐾 FMH Animal Clinic</div>
        <nav class="superadmin-nav">
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
            <a href="{{ route('superadmin.profile') }}" class="{{ request()->routeIs('superadmin.profile*') ? 'active' : '' }}">Profile</a>
            <a href="{{ route('superadmin.logout') }}">Logout</a>
        </nav>
    </header>
    @include('partials.phase-notice')
    @if (session('status') || $errors->any())
        <div style="max-width: 1400px; margin: 25px auto 0; padding: 0 30px; box-sizing: border-box;">
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
