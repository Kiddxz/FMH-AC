<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FMH Animal Clinic | Admin')</title>
    <link rel="stylesheet" href="{{ asset('css/animal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fmh-sidebar.css') }}">
    <script src="{{ asset('js/animal.js') }}" defer></script>
</head>
@php
    // Side menu of the Admin panel: [label, route, routes that make it active, icon]
    $menu = [
        ['title' => null, 'items' => [
            ['Dashboard', 'admin.dashboard', ['admin.dashboard'], 'dashboard'],
        ]],
        ['title' => 'Clinic', 'items' => [
            ['Appointments', 'admin.appointments.index', ['admin.appointments.*'], 'calendar'],
            ['Patient Flow', 'admin.flow.index', ['admin.flow.*'], 'flow'],
            ['Pets', 'admin.pets.index', ['admin.pets.*'], 'paw'],
            ['Pet Records', 'admin.records.index', ['admin.records.*'], 'records'],
            ['Waivers', 'admin.waivers.index', ['admin.waivers.*', 'admin.waiver-templates.*'], 'waiver'],
        ]],
        ['title' => 'Customers & Services', 'items' => [
            ['Customers', 'admin.customers.index', ['admin.customers.*'], 'users'],
            ['Services', 'admin.services.index', ['admin.services.*'], 'services'],
        ]],
        ['title' => 'Business', 'items' => [
            ['Payments', 'admin.transactions.index', ['admin.transactions.*'], 'payments'],
            ['POS System', 'admin.pos.overview', ['admin.pos.*'], 'pos'],
            ['Inventory', 'admin.inventory.index', ['admin.inventory.*'], 'inventory'],
            ['Reports', 'admin.reports.index', ['admin.reports.*'], 'reports'],
        ]],
    ];
@endphp
<body class="@yield('body_class', 'admin-layout') fmh-app has-sidebar">
    {{-- Side menu on the left (like the PayMongo dashboard): it can be made small with the button in the top bar.
         On a phone it opens with the Menu button (see partials/sidebar and animal.js). --}}
    @include('partials.sidebar', ['area' => 'admin', 'menu' => $menu])
    <div class="app-main">
        <div class="topbar">
            <button type="button" class="sb-collapse" title="Hide or show the menu" aria-label="Hide or show the menu" aria-controls="panelMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="topbar-logo"><img src="{{ asset('image/fmh-logo-icon.png') }}" alt="">FMH Animal Clinic</a>
            <div class="topbar-right">
                @include('partials.inventory-bell', ['area' => 'admin'])
                @include('partials.user-menu', ['profileRoute' => 'admin.profile'])
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
        @hasSection('footer')
            <footer>@yield('footer')</footer>
        @endif
    </div>
    @stack('scripts')
</body>
</html>
