<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Staff')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-sidebar.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
@php
    // Side menu of the Staff panel: [label, route, routes that make it active, icon]
    $menu = [
        ['title' => null, 'items' => [
            ['Dashboard', 'staff.dashboard', ['staff.dashboard'], 'dashboard'],
        ]],
        ['title' => 'Clinic', 'items' => [
            ['Appointments', 'staff.appointments.index', ['staff.appointments.*'], 'calendar'],
            ['Patient Flow', 'staff.flow.index', ['staff.flow.*'], 'flow'],
            ['Pets', 'staff.pets.index', ['staff.pets.*'], 'paw'],
            ['Waivers', 'staff.waivers.index', ['staff.waivers.*'], 'waiver'],
        ]],
        ['title' => 'Front Desk', 'items' => [
            ['Customers', 'staff.customers.index', ['staff.customers.*'], 'users'],
            ['POS', 'staff.pos.create', ['staff.pos.*', 'staff.transactions.*'], 'pos'],
        ]],
        ['title' => 'Business', 'items' => [
            ['Inventory', 'staff.inventory.index', ['staff.inventory.*', 'staff.suppliers.*'], 'inventory'],
            ['Reports', 'staff.reports.index', ['staff.reports.*'], 'reports'],
        ]],
    ];
@endphp
<body class="@yield('body_class', 'admin-layout') fmh-app has-sidebar">
    {{-- Side menu on the left (like the PayMongo dashboard): it can be made small with the button in the top bar.
         On a phone it opens with the Menu button (see partials/sidebar and animal.js).
         .app-frame = side menu + page in one row; the footer comes after it, so the menu never covers the footer. --}}
    <div class="app-frame">
    @include('partials.sidebar', ['area' => 'staff', 'menu' => $menu])
    <div class="app-main">
        <div class="topbar">
            <button type="button" class="sb-collapse" title="Hide or show the menu" aria-label="Hide or show the menu" aria-controls="panelMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
            </button>
            <a href="{{ route('staff.dashboard') }}" class="topbar-logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="">FMH Animal Clinic</a>
            <div class="topbar-right">
                @include('partials.inventory-bell', ['area' => 'staff'])
                @include('partials.user-menu', ['profileRoute' => 'staff.profile'])
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="panelMenu">☰ Menu</button>
            </div>
        </div>
        @include('partials.phase-notice')
        @if (session('status') || $errors->any())
            <div style="max-width: 1200px; margin: 25px auto 0; padding: 0 20px;">
                @include('partials.alerts')
            </div>
        @endif
        <div class="app-content">
            @yield('content')
        </div>
    </div>
    </div>
    {{-- the footer is below the side menu and the page, across the whole width --}}
    @hasSection('footer')
        <footer>@yield('footer')</footer>
    @endif
    @stack('scripts')
</body>
</html>
