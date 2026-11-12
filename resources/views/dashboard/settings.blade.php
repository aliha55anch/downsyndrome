@extends('layouts.dashboard')
@section('heading', 'Account settings')
@section('dashboard-content')
<section class="dashboard-panel"><span class="eyebrow">Security</span><h2>Password</h2><p>Use the secure password page to update your password.</p><a class="btn btn-outline" href="{{ route('password.change') }}">Change password</a></section>
<section class="dashboard-panel"><span class="eyebrow">Email</span><h2>Change email</h2><p class="muted">A confirmation link will be sent to the new address. Your current email stays active until confirmed.</p><form method="POST" action="{{ route('dashboard.settings.email') }}" class="form-row">@csrf<input type="email" name="email" placeholder="New email address" required><button class="btn btn-blue">Send confirmation</button></form></section>
<section class="dashboard-panel danger-zone"><span class="eyebrow">Permanent</span><h2>Delete account</h2><p>This cannot be undone. We will email a confirmation link before deleting your account and its family data.</p><form method="POST" action="{{ route('dashboard.settings.delete') }}">@csrf<input type="password" name="password" placeholder="Current password" required><button class="btn btn-danger">Request account deletion</button></form></section>
@endsection
