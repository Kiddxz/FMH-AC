<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
<body class="@yield('body_class', 'dashboard-page')">
    <header class="app-header">
        <h2 class="logo">🐾 FMH Animal Clinic</h2>
        <nav class="app-nav">
            <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('portal.appointments.create') }}" class="{{ request()->routeIs('portal.appointments.create') ? 'active' : '' }}">
                Book Appointment
            </a>
            <a href="{{ route('portal.pets.index') }}" class="{{ request()->routeIs('portal.pets.*') ? 'active' : '' }}">
                My Pets
            </a>
            <a href="{{ route('portal.appointments.index') }}" class="{{ request()->routeIs('portal.appointments.index') ? 'active' : '' }}">
                Appointment History
            </a>
            @php $toSign = auth()->user()->customer?->waivers()->where('status', 'pending')->count() ?? 0; @endphp
            <a href="{{ route('portal.waivers.index') }}" class="{{ request()->routeIs('portal.waivers.*') ? 'active' : '' }}">
                Waivers @if ($toSign > 0)<span style="background: #d9534f; color: white; border-radius: 10px; padding: 1px 7px; font-size: 12px;">{{ $toSign }}</span>@endif
            </a>
        </nav>
        <a href="{{ route('portal.profile') }}" class="profile-link {{ request()->routeIs('portal.profile*') ? 'active-profile' : '' }}">
            👤 Profile
        </a>
    </header>
    @include('partials.phase-notice')
    @if (session('status') || $errors->any())
        <div style="max-width: 900px; margin: 25px auto 0; padding: 0 20px;">
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
