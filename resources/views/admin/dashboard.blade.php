@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-title admin-dashboard-heading">
    <span class="eyebrow">Platform overview</span>
    <h1>Admin Dashboard</h1>
    <p>A clear overview of users, professional applications, and platform activity.</p>
</div>

<div class="grid admin-stat-grid">
    <a class="card admin-stat-card" href="{{ route('admin.users.index') }}" aria-label="View all users"><span class="admin-stat-icon" aria-hidden="true">👥</span><h3>Total users</h3><p class="big-number">{{ $totalUsers }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.users.index', ['role' => 'parent']) }}" aria-label="View parent accounts"><span class="admin-stat-icon" aria-hidden="true">🧑‍🧑‍🧒</span><h3>Parents</h3><p class="big-number">{{ $totalParents }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.users.index', ['role' => 'doctors']) }}" aria-label="View doctor accounts"><span class="admin-stat-icon" aria-hidden="true">🩺</span><h3>Doctor accounts</h3><p class="big-number">{{ $totalDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'pending']) }}" aria-label="View pending doctor applications"><span class="admin-stat-icon" aria-hidden="true">⏳</span><h3>Pending doctors</h3><p class="big-number">{{ $pendingDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'under_review']) }}" aria-label="View doctor applications under review"><span class="admin-stat-icon" aria-hidden="true">🔎</span><h3>Under review</h3><p class="big-number">{{ $underReviewDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'approved']) }}" aria-label="View approved doctors"><span class="admin-stat-icon" aria-hidden="true">✅</span><h3>Approved doctors</h3><p class="big-number">{{ $approvedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'rejected']) }}" aria-label="View rejected doctor applications"><span class="admin-stat-icon" aria-hidden="true">↩️</span><h3>Rejected</h3><p class="big-number">{{ $rejectedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.doctors.index', ['status' => 'suspended']) }}" aria-label="View suspended doctors"><span class="admin-stat-icon" aria-hidden="true">⛔</span><h3>Suspended</h3><p class="big-number">{{ $suspendedDoctors }}</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.messages.index') }}" aria-label="View all contact messages"><span class="admin-stat-icon" aria-hidden="true">✉️</span><h3>Contact messages</h3><p class="big-number">{{ $totalContactMessages }}</p><p class="admin-stat-note">{{ $unreadContactMessages }} unread</p></a>
    <a class="card admin-stat-card" href="{{ route('admin.activities.index') }}" aria-label="View activities"><span class="admin-stat-icon" aria-hidden="true">🎨</span><h3>Activities</h3><p class="big-number">{{ $totalActivities }}</p><p class="admin-stat-note">{{ $publishedActivities }} published</p></a>
</div>

<section class="admin-dashboard-actions">
    <div class="panel-heading"><div><span class="eyebrow">Quick access</span><h2>Platform management</h2></div></div>
    <div class="admin-dashboard-action-grid">
    <a class="btn btn-blue" href="{{ route('admin.appointments.index') }}">Manage appointments</a>
    <a class="btn btn-blue" href="{{ route('admin.refunds.index') }}">Review refunds</a>
    <a class="btn btn-outline" href="{{ route('admin.notifications.index') }}">Notifications</a>
    <a class="btn btn-blue" href="{{ route('admin.activities.index') }}">Manage activities</a>
    <a class="btn btn-blue" href="{{ route('admin.doctors.index') }}">Review doctor applications</a>
    <a class="btn btn-blue" href="{{ route('admin.users.index') }}">Manage users</a>
    <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">View contact messages</a>
    </div>
</section>
@endsection
