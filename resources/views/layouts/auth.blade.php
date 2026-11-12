<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Little Steps') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
</head>
<body>
    <header class="navbar">
        <div class="container nav-inner">
            <a class="logo" href="{{ url('/') }}">Little <span>Steps</span></a>
            <button class="menu-btn" type="button" aria-label="Open menu" data-menu-toggle>Menu</button>
            <nav class="nav-links" data-main-nav>
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/about.html') }}">About</a>
                <a href="{{ route('activities.index') }}">Activities</a>
                <a href="{{ route('doctors.index') }}" @if(request()->routeIs('doctors.*')) aria-current="page" @endif>Professional Consultancy</a>
                <a href="{{ route('contact.create') }}">Contact</a>
            </nav>
            <div class="nav-account">
                @guest
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @else
                    @php($isParent = auth()->user()->hasRole('parent'))
                    @if ($isParent) @include('notifications.bell', ['indexRoute' => route('notifications.index'), 'readRouteName' => 'notifications.read', 'readAllRoute' => route('notifications.read-all')]) @endif
                    <details class="profile-menu">
                        <summary class="profile-trigger" aria-label="Open account menu">
                            @if ($isParent && auth()->user()->parentProfile?->profile_picture_path)
                                <img src="{{ route('dashboard.profile.picture') }}" alt="">
                            @else
                                <span>{{ strtoupper(substr(auth()->user()->first_name ?: auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </summary>
                        <div class="profile-dropdown">
                            @if ($isParent)<a href="{{ route('dashboard') }}">My Dashboard</a>@endif
                            @if (auth()->user()->hasRole('pending_doctor', 'approved_doctor'))<a href="{{ route('doctor.dashboard') }}">Doctor Dashboard</a>@endif
                            @if (auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
                        </div>
                    </details>
                @endguest
            </div>
        </div>
    </header>

    <main class="container section">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-error" role="alert"><ul style="margin:0;padding-left:20px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (toggle) { var change = function (visible) { var field = document.getElementById(toggle.dataset.togglePassword); if (!field) return; field.type = visible ? 'text' : 'password'; if (toggle.type !== 'checkbox') { toggle.textContent = visible ? '◉' : '◌'; toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password'); } }; if (toggle.type === 'checkbox') toggle.addEventListener('change', function () { change(toggle.checked); }); else toggle.addEventListener('click', function () { change(document.getElementById(toggle.dataset.togglePassword)?.type === 'password'); }); });
        var menuToggle = document.querySelector('[data-menu-toggle]');
        var mainNav = document.querySelector('[data-main-nav]');
        if (menuToggle && mainNav) menuToggle.addEventListener('click', function () { mainNav.classList.toggle('open'); });
        document.addEventListener('click', function (event) { document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { if (!menu.contains(event.target)) menu.removeAttribute('open'); }); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { menu.removeAttribute('open'); }); });
    </script>
</body>
</html>
