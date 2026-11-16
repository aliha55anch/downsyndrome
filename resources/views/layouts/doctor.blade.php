<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Doctor dashboard') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
    @stack('styles')
</head>
<body class="lms-body doctor-workspace">
<header class="dashboard-site-bar">
    <a class="dashboard-site-link" href="{{ url('/') }}"><span aria-hidden="true">←</span> Back to main website</a>
    <span class="dashboard-site-label">Little Steps · Professional workspace</span>
    <div class="workspace-top-actions">
        @include('notifications.bell', ['indexRoute' => route('doctor.notifications.index'), 'readRouteName' => 'doctor.notifications.read', 'readAllRoute' => route('doctor.notifications.read-all')])
        <details class="profile-menu">
            <summary class="profile-trigger" aria-label="Open account menu"><span class="profile-name">{{ auth()->user()->name }}</span></summary>
            <div class="profile-dropdown"><a href="{{ route('doctor.profile.edit') }}">Professional profile</a></div>
        </details>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="workspace-logout" type="submit">Log out</button></form>
    </div>
</header>
<div class="dashboard-shell doctor-shell">
    <aside class="dashboard-rail">
        <div class="workspace-brand-block">
            <a class="dashboard-mark" href="{{ route('doctor.dashboard') }}">Little <span>Steps</span></a>
            <p class="dashboard-greeting">Professional workspace</p>
        </div>
        <details class="workspace-sidebar-menu" open>
            <summary><span>Workspace menu</span><span aria-hidden="true">⌄</span></summary>
            <nav class="dashboard-nav" aria-label="Doctor account navigation">
                @if(auth()->user()->role === 'approved_doctor')<a href="{{ route('doctor.appointments.index') }}" @if(request()->routeIs('doctor.appointments.*')) aria-current="page" @endif>Appointments</a>@endif
                <a href="{{ route('doctor.availability.edit') }}" @if(request()->routeIs('doctor.availability.*')) aria-current="page" @endif>Consultations & availability</a>
                <a href="{{ route('doctor.dashboard') }}" @if(request()->routeIs('doctor.dashboard')) aria-current="page" @endif>Verification overview</a>
                <a href="{{ route('doctor.profile.edit') }}" @if(request()->routeIs('doctor.profile.*')) aria-current="page" @endif>Professional profile</a>
                <a href="{{ route('doctor.dashboard') }}#verification">Verification process</a>
                @if(auth()->user()->role === 'approved_doctor')<a href="{{ route('doctor.revenue') }}" @if(request()->routeIs('doctor.revenue')) aria-current="page" @endif>Revenue</a>@endif
            </nav>
        </details>
        <div class="workspace-sidebar-foot"><span class="workspace-status-dot" aria-hidden="true"></span><span>Professional account workspace</span></div>
    </aside>
    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div><small>Little Steps professional portal</small><h1>@yield('heading', 'Doctor dashboard')</h1></div>
        </header>
        @if(session('status'))<div class="site-feedback site-feedback--success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="site-feedback site-feedback--error" role="alert">{{ $errors->first() }}</div>@endif
        @yield('doctor-content')
    </main>
</div>
<script>document.addEventListener('click',function(event){document.querySelectorAll('.profile-menu[open]').forEach(function(menu){if(!menu.contains(event.target))menu.removeAttribute('open')})});document.addEventListener('keydown',function(event){if(event.key==='Escape')document.querySelectorAll('.profile-menu[open]').forEach(function(menu){menu.removeAttribute('open')})});</script>
</body>
</html>
