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
            <a href="{{ route('staff.pets.index') }}" class="{{ request()->routeIs('staff.pets.index') ? 'active' : '' }}">Pets</a>
            <a href="{{ route('staff.pets.show', 1) }}" class="{{ request()->routeIs('staff.pets.show') ? 'active' : '' }}">Pet Records</a>
            <a href="{{ route('staff.profile') }}" class="{{ request()->routeIs('staff.profile') ? 'active' : '' }}">Profile</a>
            <a href="{{ route('staff.logout') }}" class="{{ request()->routeIs('staff.logout') ? 'active' : '' }}">Logout</a>
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
