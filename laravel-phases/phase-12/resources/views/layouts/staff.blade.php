<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Staff')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'admin-layout')">
    <header class="admin-header">
        <div class="logo">🐾 FMH Animal Clinic</div>
        <nav class="admin-nav">
            <a href="{{ route('staff.dashboard') }}" class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('staff.appointments.index') }}" class="{{ request()->routeIs('staff.appointments.*') ? 'active' : '' }}">Appointments</a>
            <a href="{{ route('staff.flow.index') }}" class="{{ request()->routeIs('staff.flow.*') || request()->routeIs('staff.walk-ins.*') ? 'active' : '' }}">Patient Flow</a>
            <a href="{{ route('staff.customers.index') }}" class="{{ request()->routeIs('staff.customers.*') ? 'active' : '' }}">Customers</a>
            <a href="{{ route('staff.pets.index') }}" class="{{ request()->routeIs('staff.pets.*') ? 'active' : '' }}">Pets</a>
            <a href="{{ route('staff.inventory.index') }}" class="{{ request()->routeIs('staff.inventory.*') || request()->routeIs('staff.suppliers.*') ? 'active' : '' }}">Inventory</a>
            <a href="{{ route('staff.profile') }}" class="{{ request()->routeIs('staff.profile*') ? 'active' : '' }}">Profile</a>
            <a href="{{ route('staff.logout') }}" class="{{ request()->routeIs('staff.logout') ? 'active' : '' }}">Logout</a>
            @include('partials.inventory-bell', ['area' => 'staff'])
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
