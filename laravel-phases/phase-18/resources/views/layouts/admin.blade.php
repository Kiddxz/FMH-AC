<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'admin-layout') fmh-app">
    {{-- Phase 18: one menu style for Staff, Admin and Super Admin. Logo and user on top, menu below.
         On a phone the menu is folded under the "Menu" button (see animal.js). --}}
    <header class="panel-header">
        <div class="panel-bar">
            <a href="{{ route('admin.dashboard') }}" class="logo">🐾 FMH Animal Clinic</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="panelMenu">☰ Menu</button>
            <div class="panel-menu" id="panelMenu">
                <nav class="panel-nav" aria-label="Main menu">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.appointments.index') }}" class="{{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}">Appointments</a>
                    <a href="{{ route('admin.flow.index') }}" class="{{ request()->routeIs('admin.flow.*') ? 'active' : '' }}">Patient Flow</a>
                    <a href="{{ route('admin.pets.index') }}" class="{{ request()->routeIs('admin.pets.*') ? 'active' : '' }}">Pets</a>
                    <a href="{{ route('admin.records.index') }}" class="{{ request()->routeIs('admin.records.*') ? 'active' : '' }}">Pet Records</a>
                    <a href="{{ route('admin.waivers.index') }}" class="{{ request()->routeIs('admin.waivers.*', 'admin.waiver-templates.*') ? 'active' : '' }}">Waivers</a>
                    <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">Customers</a>
                    <a href="{{ route('admin.services.index') }}" class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">Services</a>
                    <a href="{{ route('admin.transactions.index') }}" class="{{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">Payments</a>
                    <a href="{{ route('admin.inventory.index') }}" class="{{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">Inventory</a>
                    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
                </nav>
                <div class="panel-user">
                    @include('partials.inventory-bell', ['area' => 'admin'])
                    <span class="panel-user-name">
                        <strong>{{ auth()->user()->full_name }}</strong>
                        <small>{{ auth()->user()->role?->name }}</small>
                    </span>
                    <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile*') ? 'active' : '' }}">👤 Profile</a>
                    <a href="{{ route('admin.logout') }}" class="panel-logout">Logout</a>
                </div>
            </div>
        </div>
    </header>
    @include('partials.phase-notice')
    @if (session('status') || $errors->any())
        <div style="max-width: 1200px; margin: 25px auto 0; padding: 0 20px;">
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
