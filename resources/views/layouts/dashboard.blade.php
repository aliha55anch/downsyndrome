<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My dashboard') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
</head>
<body class="lms-body parent-workspace">
<header class="dashboard-site-bar">
    <a class="dashboard-site-link" href="{{ url('/') }}"><span aria-hidden="true">←</span> Back to main website</a>
    <span class="dashboard-site-label">Little Steps · Parent space</span>
</header>
<div class="dashboard-shell">
    <aside class="dashboard-rail">
        <div class="workspace-brand-block">
            <a class="dashboard-mark" href="{{ route('dashboard') }}">Little <span>Steps</span></a>
            <p class="dashboard-greeting">Your private learning space</p>
        </div>
        <details class="workspace-sidebar-menu">
            <summary><span>Workspace menu</span><span aria-hidden="true">⌄</span></summary>
            <nav class="dashboard-nav" aria-label="Learning space navigation">
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
                <a href="{{ route('dashboard.progress') }}" @if(request()->routeIs('dashboard.progress')) aria-current="page" @endif>Activity Progress</a>
                <a href="{{ route('dashboard.profile') }}" @if(request()->routeIs('dashboard.profile')) aria-current="page" @endif>Profile</a>
                <a href="{{ route('dashboard.settings') }}" @if(request()->routeIs('dashboard.settings')) aria-current="page" @endif>Settings</a>
                <a href="{{ route('appointments.history') }}" @if(request()->routeIs('appointments.history')) aria-current="page" @endif>Appointment history</a>
                <a href="{{ route('appointments.doctors-consulted') }}" @if(request()->routeIs('appointments.doctors-consulted')) aria-current="page" @endif>Doctors consulted</a>
                <a href="{{ route('payments.history') }}" @if(request()->routeIs('payments.history')) aria-current="page" @endif>Payment history</a>
            </nav>
        </details>
        <div class="workspace-sidebar-foot"><span class="workspace-status-dot" aria-hidden="true"></span><span>Private family space</span></div>
    </aside>
    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div><small>Little Steps learning space</small><h1>@yield('heading', 'Welcome back')</h1></div>
            @include('notifications.bell', ['indexRoute' => route('notifications.index'), 'readRouteName' => 'notifications.read', 'readAllRoute' => route('notifications.read-all')])
            <details class="profile-menu">
                <summary class="profile-trigger" aria-label="Open profile menu">
                    @if (auth()->user()->parentProfile?->profile_picture_path)<img src="{{ route('dashboard.profile.picture') }}" alt="Profile picture">@else<span>{{ strtoupper(substr(auth()->user()->first_name ?: auth()->user()->name, 0, 1)) }}</span>@endif
                </summary>
                <div class="profile-dropdown"><a href="{{ route('dashboard') }}">My dashboard</a><a href="{{ route('dashboard.profile') }}">Profile</a><a href="{{ route('dashboard.settings') }}">Settings</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form></div>
            </details>
        </header>
        @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif
        @yield('dashboard-content')
    </main>
</div>
<script>
document.addEventListener('click', function (event) { document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { if (!menu.contains(event.target)) menu.removeAttribute('open'); }); });
document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { menu.removeAttribute('open'); }); });
</script>
</body>
</html>
