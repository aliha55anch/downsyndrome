@extends('layouts.admin')

@section('title', 'User details')

@section('content')
<div class="page-title admin-dashboard-heading admin-user-detail-heading">
    <span class="eyebrow">Account directory</span>
    <h1>User details</h1>
    <p>Review and update account information for {{ $user->name }}.</p>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="form-box admin-panel admin-user-edit-form">
    @csrf
    @method('PUT')
    <div class="panel-heading"><div><span class="eyebrow">Profile</span><h2>Account information</h2><p>Keep the user’s contact and profile details up to date.</p></div></div>
    <div class="admin-user-form-grid">
        <div><label for="first_name">First name</label><input id="first_name" name="first_name" value="{{ $user->first_name }}" autocomplete="given-name" required></div>
        <div><label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ $user->last_name }}" autocomplete="family-name" required></div>
        <div class="admin-user-form-grid__wide"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ $user->email }}" autocomplete="email" required></div>
        <div><label for="role">Role</label><select id="role" name="role"><option value="parent" @selected($user->role === 'parent')>Parent</option><option value="admin" @selected($user->role === 'admin')>Administrator</option></select></div>
        <div><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ $user->parentProfile?->date_of_birth?->format('Y-m-d') }}"></div>
        <div class="admin-user-form-grid__wide"><label for="contact">Contact number</label><input id="contact" name="contact" value="{{ $user->parentProfile?->contact }}" autocomplete="tel"></div>
    </div>
    <div class="admin-user-form-actions"><button class="btn btn-blue" type="submit">Save changes</button><a class="btn btn-outline" href="{{ route('admin.users.index') }}">Back to users</a></div>
</form>

<section class="admin-panel admin-user-children">
    <div class="panel-heading"><div><span class="eyebrow">Family profiles</span><h2>Children</h2><p>Child profiles connected to this family account.</p></div><span class="admin-users-count">{{ $user->children->count() }} {{ \Illuminate\Support\Str::plural('profile', $user->children->count()) }}</span></div>
    @forelse($user->children as $child)
        <div class="admin-user-child-row"><strong>{{ $child->name }}</strong><span>Date of birth</span><time datetime="{{ $child->date_of_birth?->format('Y-m-d') }}">{{ $child->date_of_birth?->format('M j, Y') ?? 'Not provided' }}</time></div>
    @empty
        <p class="admin-user-no-children">No child profiles are linked to this account.</p>
    @endforelse
</section>

<section class="admin-user-danger-zone"><div><strong>Delete this account</strong><p>This permanently removes the user and their associated data.</p></div><form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" onclick="return confirm('Delete this user and their data?')">Delete user</button></form></section>
@endsection
