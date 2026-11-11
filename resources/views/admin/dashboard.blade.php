@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-title">
    <h1>Admin Dashboard</h1>
    <p>Overview of the current Little Steps database.</p>
</div>

<div class="grid admin-stat-grid">
    <a class="card admin-stat-card" href="{{ route('admin.users.index') }}" aria-label="View all users"><div class="card-icon">👥</div><h3>Total users</h3><p class="big-number">{{ $totalUsers }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.users.index', ['role' => 'parent']) }}" aria-label="View parent accounts"><div class="card-icon">🧑‍🧑‍🧒</div><h3>Parents</h3><p class="big-number">{{ $totalParents }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.users.index', ['role' => 'doctors']) }}" aria-label="View doctor accounts"><div class="card-icon">🩺</div><h3>Doctor accounts</h3><p class="big-number">{{ $totalDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'pending']) }}" aria-label="View pending doctor applications"><div class="card-icon">⏳</div><h3>Pending doctors</h3><p class="big-number">{{ $pendingDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'under_review']) }}" aria-label="View doctor applications under review"><div class="card-icon">🔎</div><h3>Under review</h3><p class="big-number">{{ $underReviewDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'approved']) }}" aria-label="View approved doctors"><div class="card-icon">✅</div><h3>Approved doctors</h3><p class="big-number">{{ $approvedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'rejected']) }}" aria-label="View rejected doctor applications"><div class="card-icon">↩️</div><h3>Rejected</h3><p class="big-number">{{ $rejectedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'suspended']) }}" aria-label="View suspended doctors"><div class="card-icon">⛔</div><h3>Suspended</h3><p class="big-number">{{ $suspendedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.messages.index') }}" aria-label="View all contact messages"><div class="card-icon">✉️</div><h3>Contact messages</h3><p class="big-number">{{ $totalContactMessages }}</p><p class="muted">{{ $unreadContactMessages }} unread</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.activities.index') }}" aria-label="View activities"><h3>Activities</h3><p class="big-number">{{ $totalActivities }}</p><p class="muted">{{ $publishedActivities }} published</p></a>
</div>

<div class="btn-row">
    <a class="btn btn-blue" href="{{ route('admin.appointments.index') }}">Manage appointments</a>
    <a class="btn btn-blue" href="{{ route('admin.refunds.index') }}">Review refunds</a>
    <a class="btn btn-outline" href="{{ route('admin.notifications.index') }}">Notifications</a>
    <a class="btn btn-blue" href="{{ route('admin.activities.index') }}">Manage activities</a>
    <a class="btn btn-blue" href="{{ route('admin.doctors.index') }}">Review doctor applications</a>
    <a class="btn btn-blue" href="{{ route('admin.users.index') }}">Manage users</a>
    <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">View contact messages</a>
</div>
@endsection
