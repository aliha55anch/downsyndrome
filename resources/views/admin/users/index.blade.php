@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<div class="page-title admin-dashboard-heading">
    <span class="eyebrow">Account directory</span>
    <h1>Manage Users</h1>
    <p>Search accounts and update roles securely. Showing: {{ $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'all accounts' }}.</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar admin-users-filter">
    <div class="form-row">
        <div>
            <label for="search">Search name or email</label>
            <input type="search" id="search" name="search" value="{{ $search }}">
        </div>
        <div>
            <label for="role">Account filter</label>
            <select id="role" name="role">
                <option value="">All accounts</option>
                <option value="parent" @selected($role === 'parent')>Parents</option>
                <option value="doctors" @selected($role === 'doctors')>All doctors</option>
                <option value="pending_doctor" @selected($role === 'pending_doctor')>Pending doctors</option>
                <option value="under_review" @selected($role === 'under_review')>Under review</option>
                <option value="approved_doctor" @selected($role === 'approved_doctor')>Approved doctors</option>
                <option value="rejected" @selected($role === 'rejected')>Rejected doctors</option>
                <option value="suspended" @selected($role === 'suspended')>Suspended doctors</option>
                <option value="admin" @selected($role === 'admin')>Administrators</option>
            </select>
        </div>
        <div class="admin-users-filter__submit">
            <button class="btn btn-blue" type="submit">Search</button>
        </div>
    </div>
</form>

<section class="admin-panel admin-users-panel">
    <div class="panel-heading"><div><span class="eyebrow">User accounts</span><h2>Accounts</h2><p>Review account details, email status, and available role actions.</p></div><span class="admin-users-count">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}</span></div>
    <div class="table-wrap admin-users-table-wrap">
        <table class="admin-users-table">
            <thead><tr><th scope="col">User</th><th scope="col">Role</th><th scope="col">Email status</th><th scope="col">Account actions</th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="admin-users-identity"><strong>{{ $user->name }}</strong><span>{{ $user->email }}</span></td>
                    <td><span class="admin-user-role">{{ ucwords(str_replace('_', ' ', $user->role)) }}</span></td>
                    <td><span @class(['admin-user-status', 'is-verified' => $user->hasVerifiedEmail(), 'is-unverified' => ! $user->hasVerifiedEmail()])>{{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</span></td>
                    <td class="admin-users-actions">
                        <div class="admin-user-primary-action">
                        @if($user->doctorProfile)
                            <a class="btn btn-outline btn-small" href="{{ route('admin.doctors.show', $user->doctorProfile) }}">Review application</a>
                        @else
                            <a class="btn btn-outline btn-small" href="{{ route('admin.users.show', $user) }}">View / edit</a>
                        @endif
                        </div>
                        @if ($user->is(auth()->user()))
                            <span class="admin-user-action-note">Your account</span>
                        @elseif($user->doctorProfile || $user->hasRole(\App\Models\User::ROLE_PENDING_DOCTOR, \App\Models\User::ROLE_APPROVED_DOCTOR))
                            <span class="admin-user-action-note">Role managed in Doctor Management</span>
                        @else
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="admin-user-role-form">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="role-{{ $user->id }}">Role for {{ $user->name }}</label>
                                <select id="role-{{ $user->id }}" name="role">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}" @selected($user->role === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline btn-small" type="submit">Save role</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td class="admin-users-empty" colspan="4">No accounts match these filters. Try a different search or account type.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</section>
@endsection
