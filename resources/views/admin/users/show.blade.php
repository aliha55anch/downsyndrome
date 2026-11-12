@extends('layouts.admin')
@section('title', 'User details')
@section('content')
<div class="page-title"><h1>User details</h1><p>Review or update this account.</p></div>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="form-box">
    @csrf @method('PUT')
    <div class="form-row"><div><label for="first_name">First name</label><input id="first_name" name="first_name" value="{{ $user->first_name }}" required></div><div><label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ $user->last_name }}" required></div></div>
    <label for="email">Email</label><input id="email" type="email" name="email" value="{{ $user->email }}" required>
    <div class="form-row"><div><label for="role">Role</label><select id="role" name="role"><option value="parent" @selected($user->role === 'parent')>parent</option><option value="admin" @selected($user->role === 'admin')>admin</option></select></div><div><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ $user->parentProfile?->date_of_birth?->format('Y-m-d') }}"></div><div><label for="contact">Contact</label><input id="contact" name="contact" value="{{ $user->parentProfile?->contact }}"></div></div>
    <button class="btn btn-blue">Save user</button>
</form>
<section class="form-box"><h2>Children</h2>@forelse($user->children as $child)<p>{{ $child->name }} — {{ $child->date_of_birth?->format('Y-m-d') }}</p>@empty<p>No child profiles.</p>@endforelse</section>
<form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<button class="btn btn-danger" onclick="return confirm('Delete this user and their data?')">Delete user</button></form>
@endsection
