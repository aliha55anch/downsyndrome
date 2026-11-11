<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
</head>
<body class="admin-workspace">
    <header class="dashboard-site-bar admin-site-bar">
        <a class="dashboard-site-link" href="{{ url('/') }}"><span aria-hidden="true">←</span> Back to main website</a>
        <span class="dashboard-site-label">Little Steps · Administration</span>
        <div class="workspace-top-actions">
            @include('notifications.bell', ['indexRoute' => route('admin.notifications.index'), 'readRouteName' => 'admin.notifications.read', 'readAllRoute' => route('admin.notifications.read-all')])
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="workspace-logout" type="submit">Log out</button></form>
        </div>
    </header>
    <div class="admin-workspace-shell">
        <aside class="dashboard-rail admin-rail">
            <div class="workspace-brand-block">
                <a class="dashboard-mark" href="{{ route('admin.dashboard') }}">Little <span>Steps</span></a>
                <p class="dashboard-greeting">Administration workspace</p>
            </div>
            <details class="workspace-sidebar-menu">
                <summary><span>Workspace menu</span><span aria-hidden="true">⌄</span></summary>
                <nav class="dashboard-nav" aria-label="Administration navigation">
                    <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Overview</a>
                    <a href="{{ route('admin.activities.index') }}" @if(request()->routeIs('admin.activities.*')) aria-current="page" @endif>Activities</a>
                    <a href="{{ route('admin.doctors.index') }}" @if(request()->routeIs('admin.doctors.*')) aria-current="page" @endif>Doctor Management</a>
                    <a href="{{ route('admin.appointments.index') }}" @if(request()->routeIs('admin.appointments.*')) aria-current="page" @endif>Appointments</a>
                    <a href="{{ route('admin.refunds.index') }}" @if(request()->routeIs('admin.refunds.*')) aria-current="page" @endif>Refunds</a>
                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>Users</a>
                    <a href="{{ route('admin.messages.index') }}" @if(request()->routeIs('admin.messages.*')) aria-current="page" @endif>Contact messages</a>
                    <a href="{{ route('admin.notifications.create') }}" @if(request()->routeIs('admin.notifications.create')) aria-current="page" @endif>Send notification</a>
                </nav>
            </details>
            <div class="workspace-sidebar-foot"><span class="workspace-status-dot" aria-hidden="true"></span><span>Administrator access</span></div>
        </aside>
        <main class="admin-workspace-main">
            @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
