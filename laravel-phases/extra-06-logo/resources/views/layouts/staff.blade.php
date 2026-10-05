<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Staff')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'admin-layout') fmh-app">
    {{-- Phase 18: one menu style for Staff, Admin and Super Admin. Logo and user on top, menu below.
         On a phone the menu is folded under the "Menu" button (see animal.js). --}}
    <header class="panel-header">
        <div class="panel-bar">
            <a href="{{ route('staff.dashboard') }}" class="logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="" class="logo-mark" style="height: 1.6em; width: auto; vertical-align: middle; margin-right: 8px;">FMH Animal Clinic</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="panelMenu">☰ Menu</button>
            <div class="panel-menu" id="panelMenu">
                <nav class="panel-nav" aria-label="Main menu">
                    <a href="{{ route('staff.dashboard') }}" class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('staff.appointments.index') }}" class="{{ request()->routeIs('staff.appointments.*') ? 'active' : '' }}">Appointments</a>
                    <a href="{{ route('staff.flow.index') }}" class="{{ request()->routeIs('staff.flow.*') || request()->routeIs('staff.walk-ins.*') ? 'active' : '' }}">Patient Flow</a>
                    <a href="{{ route('staff.customers.index') }}" class="{{ request()->routeIs('staff.customers.*') ? 'active' : '' }}">Customers</a>
                    <a href="{{ route('staff.pets.index') }}" class="{{ request()->routeIs('staff.pets.*') ? 'active' : '' }}">Pets</a>
                    <a href="{{ route('staff.waivers.index') }}" class="{{ request()->routeIs('staff.waivers.*') ? 'active' : '' }}">Waivers</a>
                    <a href="{{ route('staff.pos.create') }}" class="{{ request()->routeIs('staff.pos.*', 'staff.transactions.*') ? 'active' : '' }}">POS</a>
                    <a href="{{ route('staff.inventory.index') }}" class="{{ request()->routeIs('staff.inventory.*') || request()->routeIs('staff.suppliers.*') ? 'active' : '' }}">Inventory</a>
                    <a href="{{ route('staff.reports.index') }}" class="{{ request()->routeIs('staff.reports.*') ? 'active' : '' }}">Reports</a>
                </nav>
                <div class="panel-user">
                    @include('partials.inventory-bell', ['area' => 'staff'])
                    <span class="panel-user-name">
                        <strong>{{ auth()->user()->full_name }}</strong>
                        <small>{{ auth()->user()->role?->name }}</small>
                    </span>
                    <a href="{{ route('staff.profile') }}" class="{{ request()->routeIs('staff.profile*') ? 'active' : '' }}">👤 Profile</a>
                    <a href="{{ route('staff.logout') }}" class="panel-logout">Logout</a>
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
