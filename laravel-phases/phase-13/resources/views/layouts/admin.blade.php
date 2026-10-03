<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
    <style>
        /* The menu has 13 links and a bell (Pet Records, Patient Flow, Waivers and Profile were added), so the spacing is tighter */
        .admin-header, .admin-dashboard-header { gap: 20px; }
        .admin-nav, .admin-dashboard-nav { gap: 12px; }
        .admin-nav a, .admin-dashboard-nav a { font-size: 14px; white-space: nowrap; }
        @media (max-width: 1340px) {
            .admin-header, .admin-dashboard-header { flex-direction: column; gap: 12px; }
            .admin-nav, .admin-dashboard-nav { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body class="@yield('body_class', 'admin-layout')">
    {{-- The dashboard keeps its original header classes; every other page uses admin-header. --}}
    <header class="@yield('header_class', 'admin-header')">
        <div class="logo">🐾 FMH Animal Clinic</div>
        <nav class="@yield('nav_class', 'admin-nav')">
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
            <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile*') ? 'active' : '' }}">Profile</a>
            <a href="{{ route('admin.logout') }}" class="{{ request()->routeIs('admin.logout') ? 'active' : '' }}">Logout</a>
            @include('partials.inventory-bell', ['area' => 'admin'])
        </nav>
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
