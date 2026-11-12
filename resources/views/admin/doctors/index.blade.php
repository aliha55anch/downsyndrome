@extends('layouts.admin')
@section('title', 'Doctor Management')
@section('content')
<div class="page-title"><h1>Doctor Management</h1><p>Review professional applications and manage verified access.</p><a class="btn btn-outline" href="{{ route('admin.doctors.specialties') }}">Manage specialty options</a></div>
<form method="GET" action="{{ route('admin.doctors.index') }}" class="filter-bar doctor-filter-form"><div class="form-row"><div><label for="search">Doctor name or email</label><input id="search" type="search" name="search" value="{{ $search }}"></div><div><label for="specialty">Specialty</label><input id="specialty" name="specialty" value="{{ $specialty }}"></div><div><label for="city">City</label><input id="city" name="city" value="{{ $city }}"></div><div><label for="status">Verification status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(\App\Models\DoctorProfile::STATUSES as $option)<option value="{{ $option }}" @selected($status === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>@endforeach</select></div><div style="align-self:end"><button class="btn btn-blue" type="submit">Filter</button></div></div></form>
<div class="doctor-admin-list">
    @forelse($profiles as $profile)
        <article class="doctor-admin-card"><div class="doctor-admin-card-main"><span class="doctor-status status-{{ $profile->status }}">{{ ucwords(str_replace('_', ' ', $profile->status)) }}</span> · <span>{{ $profile->is_public ? 'Public profile' : 'Hidden profile' }}</span><h2>{{ $profile->user->name }}</h2><p>{{ $profile->user->email }} · {{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }}</p><p>{{ $profile->city }} · {{ $profile->license_number }}</p><small>Submitted {{ $profile->submitted_at?->format('M j, Y') ?? $profile->created_at->format('M j, Y') }}</small></div><a class="btn btn-outline" href="{{ route('admin.doctors.show', $profile) }}">Review application</a></article>
    @empty<div class="empty-state"><h2>No doctor applications found</h2><p>Try a different search or status filter.</p></div>@endforelse
</div>
{{ $profiles->links() }}
@endsection
