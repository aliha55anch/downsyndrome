@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<div class="page-title">
    <h1>Manage Users</h1>
    <p>Search accounts and update roles securely. Showing: {{ $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'all accounts' }}.</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
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
        <div style="align-self:end;">
            <button class="btn btn-blue" type="submit">Search</button>
        </div>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Email status</th><th>Details</th><th>Change role</th></tr></thead>
        <tbody>
        @forelse ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $user->role)) }}</td>
                <td>{{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</td><td>@if($user->doctorProfile)<a class="btn btn-outline btn-small" href="{{ route('admin.doctors.show', $user->doctorProfile) }}">Review application</a>@else<a class="btn btn-outline btn-small" href="{{ route('admin.users.show',$user) }}">View / edit</a>@endif</td>
                <td>
                    @if ($user->is(auth()->user()))
                        <span class="muted">Your account</span>
                    @elseif($user->doctorProfile || $user->hasRole(\App\Models\User::ROLE_PENDING_DOCTOR, \App\Models\User::ROLE_APPROVED_DOCTOR))
                        <span class="muted">Use Doctor Management</span>
                    @else
                        <form method="POST" action="{{ route('admin.users.role', $user) }}">
                            @csrf
                            @method('PATCH')
                            <select name="role" aria-label="Role for {{ $user->name }}">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline btn-small" type="submit">Save</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $users->links() }}
@endsection
