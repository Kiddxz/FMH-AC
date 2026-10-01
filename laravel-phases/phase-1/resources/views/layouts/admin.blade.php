<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'admin-layout')">
    {{-- The dashboard keeps its original header classes; every other page uses admin-header.
         "Pet Records" and "Profile" menu links are added in Phase 7 (the menu has no room yet). --}}
    <header class="@yield('header_class', 'admin-header')">
        <div class="logo">🐾 FMH Animal Clinic</div>
        <nav class="@yield('nav_class', 'admin-nav')">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.appointments.index') }}" class="{{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}">Appointments</a>
            <a href="{{ route('admin.pets.index') }}" class="{{ request()->routeIs('admin.pets.*') ? 'active' : '' }}">Pets</a>
            <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.services.index') }}" class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">Services</a>
            <a href="{{ route('admin.transactions.index') }}" class="{{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">Payments</a>
            <a href="{{ route('admin.inventory.index') }}" class="{{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">Inventory</a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('admin.logout') }}" class="{{ request()->routeIs('admin.logout') ? 'active' : '' }}">Logout</a>
        </nav>
    </header>
    @include('partials.phase-notice')
    @yield('content')
    @hasSection('footer')
        <footer>@yield('footer')</footer>
    @endif
    @stack('scripts')
</body>
</html>
